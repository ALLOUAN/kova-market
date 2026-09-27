<?php

namespace App\Listeners;

use App\Enums\Permission;
use App\Events\OrderPlaced;
use App\Filament\Resources\Orders\OrderResource;
use App\Notifications\NewOrderForStaff;
use App\Notifications\OrderUpdateForCustomer;
use App\Support\Money;
use App\Support\StaffRecipients;
use Filament\Actions\Action;
use Filament\Notifications\Notification as BackOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * New order (F-130): SMS + e-mail to the customer, e-mail + back-office alert to the staff handling orders.
 */
class NotifyOrderPlaced implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        Notification::send(OrderUpdateForCustomer::recipientOf($order), new OrderUpdateForCustomer($order, OrderUpdateForCustomer::PLACED));

        $staff = StaffRecipients::with(Permission::ManageOrders);

        Notification::send($staff, new NewOrderForStaff($order));

        BackOfficeAlert::make()
            ->title("Nouvelle commande {$order->number}")
            ->body("{$order->customer_name} · ".Money::format($order->total)." · {$order->commune_name}")
            ->icon('heroicon-o-shopping-cart')
            ->actions([Action::make('open')->label('Ouvrir')->url(OrderResource::getUrl('view', ['record' => $order]))])
            ->sendToDatabase($staff);
    }
}
