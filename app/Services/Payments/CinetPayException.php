<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * CinetPay unreachable, not configured or refusing a call. The message can be shown to the customer.
 */
class CinetPayException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(string $message, public readonly array $response = [])
    {
        parent::__construct($message);
    }
}
