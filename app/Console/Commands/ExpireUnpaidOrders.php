<?php

namespace App\Console\Commands;

use App\Services\Payments\OnlinePayments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * F-056: online orders still unpaid after the timeout (30 minutes unless set in the store settings) are cancelled
 * and their stock put back on sale; cash-on-delivery orders are never concerned.
 */
#[Signature('payments:expire-unpaid')]
#[Description('Cancel the online orders left unpaid past the payment timeout')]
class ExpireUnpaidOrders extends Command
{
    public function handle(OnlinePayments $payments): int
    {
        $count = $payments->expireUnpaid();

        $this->info("{$count} commande(s) non payée(s) annulée(s).");

        return self::SUCCESS;
    }
}
