<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Events\OrderStatusChanged;
use App\Filament\Resources\Orders\OrderResource;
use App\Notifications\OrderUpdateForCustomer;
use App\Support\StaffRecipients;
use Filament\Actions\Action;
use Filament\Notifications\Notification as BackOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Status change (F-130): the customer is told at each step of the table; a cancellation also alerts the staff.
 * Corrections going back a step are not announced to the customer.
 */
class NotifyOrderStatusChanged implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $movingForward = in_array($event->to, $event->from->next(), true);

        if ($movingForward && OrderUpdateForCustomer::concerns($event->to->value)) {
            Notification::send(OrderUpdateForCustomer::recipientOf($order), new OrderUpdateForCustomer($order, $event->to->value));
        }

        if ($event->to === OrderStatus::Cancelled) {
            BackOfficeAlert::make()
                ->title("Commande {$order->number} annulée")
                ->body($order->statusHistory()->reorder('id', 'desc')->value('note'))
                ->icon('heroicon-o-x-circle')
                ->danger()
                ->actions([Action::make('open')->label('Ouvrir')->url(OrderResource::getUrl('view', ['record' => $order]))])
                ->sendToDatabase(StaffRecipients::with(Permission::ManageOrders));
        }
    }
}
