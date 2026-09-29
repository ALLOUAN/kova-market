<?php

namespace App\Services\Payments;

/**
 * What CinetPay's status check says about a payment: final success or failure, or still under way.
 */
enum PaymentOutcome
{
    case Succeeded;
    case Failed;
    case Pending;
}
