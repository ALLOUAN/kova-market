<?php

namespace App\Services\Payments;

/**
 * A payment opened on CinetPay: where to send the customer, and the tokens that identify it later.
 */
final readonly class PaymentInitiation
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public string $paymentUrl,
        public ?string $paymentToken,
        public ?string $notifyToken,
        public ?string $transactionId,
        public array $response,
    ) {}
}
