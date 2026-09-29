<?php

namespace App\Services\Payments;

/**
 * A payment as CinetPay's status check reports it.
 */
final readonly class PaymentState
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public PaymentOutcome $outcome,
        public int $code,
        public string $status,
        public ?string $merchantTransactionId,
        public ?string $transactionId,
        public ?string $payerPhone,
        public ?string $operator,
        public array $response,
    ) {}
}
