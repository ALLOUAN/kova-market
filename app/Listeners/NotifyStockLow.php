<?php

namespace App\Listeners;

use App\Enums\Permission;
use App\Events\StockLow;
use App\Notifications\LowStockForStaff;
use App\Support\StaffRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Stock alert e-mail to the staff managing the catalog (F-135).
 */
class NotifyStockLow implements ShouldQueue
{
    public function handle(StockLow $event): void
    {
        Notification::send(StaffRecipients::with(Permission::ManageCatalog), new LowStockForStaff($event->variant));
    }
}
