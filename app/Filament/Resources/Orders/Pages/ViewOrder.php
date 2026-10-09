<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\QuantityInput;
use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\DeliveryDateForCustomer;
use App\Notifications\OrderUpdateForCustomer;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Delivery\DispatchException;
use App\Services\Orders\OrderStatusException;
use App\Services\Orders\OrderStatusManager;
use App\Services\Orders\WeighIn;
use App\Services\Orders\WeighInException;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Notification as Notifier;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Order page: one button per step the user may take next (F-121), the order slip (F-107)
 * and, for super-admins, going back one step with a reason.
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return "Commande {$this->getRecord()->number}";
    }

    protected function getHeaderActions(): array
    {
        $statuses = app(OrderStatusManager::class);

        return [
            ...collect(OrderStatus::cases())
                ->reject(fn (OrderStatus $status) => $status === OrderStatus::Received)
                ->map(fn (OrderStatus $status) => $this->stepAction($status, $statuses))
                ->all(),
            $this->weighInAction(),
            // F-123: the back-office can always give the delivery to another courier, or put it back in the queue.
            Action::make('assignCourier')
                ->label(fn (Order $record) => $record->courier_id ? 'Changer de livreur' : 'Confier à un livreur')
                ->icon('heroicon-o-truck')
                ->color('gray')
                // Shipped to the interior by carrier: no courier.
                ->visible(fn (Order $record) => $record->delivery_mode->usesCouriers() && in_array($record->status, Courier::OPEN_STATUSES, true) && auth()->user()->can(Permission::ManageOrders->value))
                ->schema(fn (Order $record) => [
                    Select::make('courier_id')
                        ->label('Livreur')
                        ->helperText('Les livreurs de la zone de la commande sont proposés en premier.')
                        ->options(fn () => app(DeliveryDispatcher::class)->courierOptions($record))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (Order $record, array $data): void {
                    try {
                        app(DeliveryDispatcher::class)->assign($record, Courier::findOrFail($data['courier_id']), auth()->user());
                    } catch (DispatchException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title("Livraison confiée à {$record->fresh()->courier->name()}")->success()->send();
                }),
            ActionGroup::make([
                // F-127: the customer is told of the planned date, and of every change.
                Action::make('deliveryDate')
                    ->label('Date de livraison prévue')
                    ->icon('heroicon-o-calendar-days')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::Received, ...Courier::OPEN_STATUSES], true) && auth()->user()->can(Permission::ManageOrders->value))
                    ->fillForm(fn (Order $record) => ['delivery_date' => $record->delivery_date])
                    ->schema([
                        DatePicker::make('delivery_date')
                            ->label('Date prévue')
                            ->helperText('Le client est prévenu par SMS (et par e-mail s’il en a donné un).')
                            ->minDate(today())
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->forceFill(['delivery_date' => $data['delivery_date']])->save();
                        Notifier::send(OrderUpdateForCustomer::recipientOf($record), new DeliveryDateForCustomer($record));
                        Notification::make()->title('Date enregistrée, le client est prévenu')->success()->send();
                    }),
                Action::make('releaseCourier')
                    ->label('Retirer au livreur')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (Order $record) => $record->courier_id !== null && in_array($record->status, Courier::OPEN_STATUSES, true) && auth()->user()->can(Permission::ManageOrders->value))
                    ->requiresConfirmation()
                    ->modalDescription('La commande repasse dans la file de sa zone.')
                    ->action(function (Order $record): void {
                        app(DeliveryDispatcher::class)->release($record, auth()->user());
                        Notification::make()->title('Commande remise dans la file de la zone')->success()->send();
                    }),
                Action::make('slip')
                    ->label('Bon de commande (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn (Order $record): StreamedResponse => response()->streamDownload(
                        fn () => print (Pdf::loadView('pdf.order-slip', ['order' => $record->load('items')])->output()),
                        "bon-de-commande-{$record->number}.pdf",
                    )),
                Action::make('rollBack')
                    ->label('Revenir à l’étape précédente')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (Order $record) => $statuses->mayRollBack($record, auth()->user()))
                    ->modalDescription(fn (Order $record) => 'La commande repassera à « '.$record->status->previous()?->getLabel().' ». Cette correction est tracée.')
                    ->schema([Textarea::make('note')->label('Motif')->required()->maxLength(255)])
                    ->action(fn (Order $record, array $data) => $this->run(fn () => $statuses->rollBack($record, auth()->user(), $data['note']))),
            ])->label('Plus')->button()->color('gray'),
        ];
    }

    /**
     * Mon Marché: the weighed quantity of each line sold by weight or volume, billed instead of the ordered one.
     */
    private function weighInAction(): Action
    {
        $weighIn = app(WeighIn::class);

        return Action::make('weighIn')
            ->label('Peser les articles')
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->visible(fn (Order $record) => $weighIn->applies($record)
                && auth()->user()->canAny([Permission::PrepareOrders->value, Permission::ManageOrders->value]))
            ->modalHeading('Pesée des articles')
            ->modalDescription('Indiquez la quantité réellement pesée : la ligne, le total de la commande et le montant à encaisser par le livreur sont recalculés. Écart de '.WeighIn::tolerance().' % au plus avec la commande.')
            ->modalSubmitActionLabel('Enregistrer la pesée')
            ->fillForm(fn (Order $record) => ['weighed' => $weighIn->weighableItems($record)->mapWithKeys(fn (OrderItem $item) => [$item->id => $item->quantity])->all()])
            ->schema(fn (Order $record) => $weighIn->weighableItems($record)->map(function (OrderItem $item) use ($weighIn) {
                $rules = $item->saleQuantity();
                [$min, $max] = $weighIn->bounds($item);

                return QuantityInput::make("weighed.{$item->id}", $item->product_name.($item->variant_label ? " ({$item->variant_label})" : ''), fn () => $item->sale_unit)
                    ->required()
                    ->helperText('Commandé : '.$rules->format($item->ordered_quantity ?? $item->quantity).' à '.Money::format($item->unit_price).$rules->priceSuffix()
                        .' · accepté de '.$rules->format($min).' à '.$rules->format($max));
            })->all())
            ->action(function (Order $record, array $data) use ($weighIn): void {
                try {
                    $order = $weighIn->record($record, array_map('intval', $data['weighed'] ?? []), auth()->user());
                } catch (WeighInException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Pesée enregistrée')->body('Nouveau total : '.Money::format($order->total).'.')->success()->send();
                $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
            });
    }

    private function stepAction(OrderStatus $status, OrderStatusManager $statuses): Action
    {
        $action = Action::make('to_'.$status->value)
            ->label(match ($status) {
                OrderStatus::Confirmed => 'Confirmer',
                OrderStatus::Preparing => 'Mettre en préparation',
                OrderStatus::Shipped => 'Marquer expédiée vers l’intérieur',
                OrderStatus::OutForDelivery => 'Mettre en livraison',
                OrderStatus::Delivered => 'Marquer livrée',
                OrderStatus::Cancelled => 'Annuler',
                default => $status->getLabel(),
            })
            ->color($status === OrderStatus::Cancelled ? 'danger' : 'primary')
            ->visible(fn (Order $record) => in_array($status, $statuses->availableSteps($record, auth()->user()), true))
            ->requiresConfirmation()
            ->action(fn (Order $record, array $data) => $this->run(fn () => $statuses->move($record, $status, auth()->user(), $data['note'] ?? null)));

        return match ($status) {
            OrderStatus::Cancelled => $action
                ->modalDescription('Le stock des articles sera remis en vente.')
                ->schema([Textarea::make('note')->label('Motif de l’annulation')->required()->maxLength(255)]),
            OrderStatus::Delivered => $action->modalDescription(fn (Order $record) => $record->payment_method === PaymentMethod::CashOnDelivery
                ? 'Le paiement à la livraison sera enregistré comme reçu.'
                : null),
            default => $action,
        };
    }

    private function run(callable $change): void
    {
        try {
            $order = $change();
        } catch (OrderStatusException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title("Commande « {$order->status->getLabel()} »")->success()->send();
        $this->refreshFormData(['status', 'payment_status']);
    }
}
