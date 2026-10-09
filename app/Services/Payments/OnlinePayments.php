<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Orders\OrderStatusManager;
use App\Services\Storefront\MetaConversions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Online payment of an order through CinetPay (F-060 to F-067), the logic of MesRévisions fitted to KOVA MARKET's
 * orders: one Payment per redirection to CinetPay; its outcome is only ever taken from CinetPay's status check
 * (return page, notification, sweep), never from what a browser or a notification claims; applying a success is
 * idempotent and locks the payment row. A paid order then follows the usual flow (confirmation, preparation...).
 */
class OnlinePayments
{
    /** Minutes an online order may stay unpaid before it is cancelled (F-056), unless set otherwise. */
    public const DEFAULT_TIMEOUT = 30;

    public function __construct(
        private CinetPayClient $cinetPay,
        private OrderStatusManager $statuses,
    ) {}

    public function isAvailable(): bool
    {
        return $this->cinetPay->isConfigured();
    }

    public function timeoutMinutes(): int
    {
        return max(10, (int) (Setting::get('payment.online_timeout_minutes') ?: self::DEFAULT_TIMEOUT));
    }

    /**
     * Opens a payment for the order at CinetPay and returns it, with the URL to send the customer to.
     *
     * @throws CinetPayException
     */
    public function start(Order $order): Payment
    {
        if (! $order->awaitsOnlinePayment()) {
            throw new CinetPayException('Cette commande n’attend pas de paiement en ligne.');
        }

        $payment = $order->payments()->create([
            'merchant_transaction_id' => $this->reference(),
            // XOF payments must be a multiple of 5 francs (CinetPay): the few francs added are recorded.
            'amount' => (int) (ceil($order->total / 5) * 5),
            'currency' => strtoupper((string) config('services.cinetpay.currency')),
            'status' => TransactionStatus::Initiated,
            'payer_phone' => $order->phone,
        ]);

        [$firstName, $lastName] = $this->names($order->customer_name);

        try {
            $initiation = $this->cinetPay->initiate([
                'merchant_transaction_id' => $payment->merchant_transaction_id,
                'amount' => $payment->amount,
                'designation' => config('storefront.name').' - commande '.$order->number,
                'email' => $order->email ?: (config('services.cinetpay.fallback_email') ?: config('storefront.contact.email')),
                'phone' => $order->phone,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'success_url' => route('payments.return', $payment),
                'failed_url' => route('payments.return', $payment),
                'notify_url' => route('payments.notify'),
            ]);
        } catch (CinetPayException $exception) {
            $payment->status = TransactionStatus::Failed;
            $payment->failure_reason = $exception->getMessage();
            $payment->record('initiation_refused', ['code' => $exception->response['code'] ?? null, 'details' => $exception->response['details']['code'] ?? null]);
            $payment->save();

            throw $exception;
        }

        $payment->fill([
            'gateway_transaction_id' => $initiation->transactionId,
            'payment_token' => $initiation->paymentToken,
            'notify_token_hash' => $initiation->notifyToken ? hash('sha256', $initiation->notifyToken) : null,
            'payment_url' => $initiation->paymentUrl,
        ]);
        $payment->record('initiated', ['transaction_id' => $initiation->transactionId, 'rounded_by' => $payment->amount - $order->total]);
        $payment->save();

        return $payment;
    }

    /**
     * Asks CinetPay where the payment stands and applies it. Payments already settled are left as they are.
     *
     * @throws CinetPayException when CinetPay cannot answer: nothing is changed then
     */
    public function synchronize(Payment $payment, string $source): Payment
    {
        if (! $payment->status->isOpen()) {
            return $payment;
        }

        $state = $this->cinetPay->status($payment->gatewayIdentifier());

        // The answer must be about this payment.
        if ($state->merchantTransactionId !== null && $state->merchantTransactionId !== $payment->merchant_transaction_id) {
            Log::warning('[CinetPay] Status of another transaction', ['payment' => $payment->merchant_transaction_id, 'answer' => $state->merchantTransactionId]);

            return $payment;
        }

        return match ($state->outcome) {
            PaymentOutcome::Succeeded => $this->markPaid($payment, $state, $source),
            PaymentOutcome::Failed => $this->close($payment, TransactionStatus::Failed, "CinetPay : {$state->status}", $state, $source),
            PaymentOutcome::Pending => $this->markPending($payment, $state, $source),
        };
    }

    /**
     * A notification from CinetPay (server to server). It only triggers a status check, and only when it carries
     * the notify_token CinetPay gave for this payment. Returns the payment it concerned, if any.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(array $payload): ?Payment
    {
        $reference = $payload['merchant_transaction_id'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        $payment = (is_string($reference) ? Payment::where('merchant_transaction_id', $reference)->first() : null)
            ?? (is_string($transactionId) ? Payment::where('gateway_transaction_id', $transactionId)->first() : null);

        if (! $payment) {
            Log::warning('[CinetPay] Notification for an unknown payment', ['merchant_transaction_id' => $reference, 'transaction_id' => $transactionId]);

            return null;
        }

        if (! $payment->notifyTokenMatches(is_string($payload['notify_token'] ?? null) ? $payload['notify_token'] : null)) {
            Log::warning('[CinetPay] Notification refused: notify_token does not match', ['payment' => $payment->merchant_transaction_id]);
            $payment->record('notification_refused');
            $payment->save();

            return null;
        }

        $payment->record('notification', ['transaction_id' => $transactionId]);
        $payment->save();

        try {
            return $this->synchronize($payment, 'notification');
        } catch (CinetPayException) {
            // CinetPay could not confirm: nothing changes; the next notification, the return page or the sweep will.
            return $payment;
        }
    }

    /**
     * Cancels the online orders left unpaid past the timeout (F-056), after a last check that CinetPay has not
     * received their payment meanwhile. Returns how many were cancelled.
     */
    public function expireUnpaid(): int
    {
        $cancelled = 0;

        Order::query()
            ->where('payment_method', PaymentMethod::Online)
            ->where('payment_status', PaymentStatus::Pending)
            ->where('status', OrderStatus::Received)
            ->where('created_at', '<=', now()->subMinutes($this->timeoutMinutes()))
            ->with('payments')
            ->each(function (Order $order) use (&$cancelled): void {
                foreach ($order->payments->filter(fn (Payment $payment) => $payment->status->isOpen()) as $payment) {
                    try {
                        $this->synchronize($payment, 'sweep');
                    } catch (CinetPayException) {
                        return; // CinetPay unreachable: the order waits for the next sweep rather than being cancelled blindly.
                    }
                }

                if ($order->refresh()->payment_status !== PaymentStatus::Pending) {
                    return;
                }

                $order->payments()->get()->filter(fn (Payment $payment) => $payment->status->isOpen())
                    ->each(fn (Payment $payment) => $this->close($payment, TransactionStatus::Cancelled, 'Délai de paiement dépassé', null, 'sweep'));

                $this->statuses->expireUnpaid($order, 'Non payée après '.$this->timeoutMinutes().' minutes : annulée automatiquement, stock remis en vente.');
                $cancelled++;
            });

        return $cancelled;
    }

    /**
     * F-067: the refund is made in CinetPay's merchant space (the API has no refund call), then recorded here.
     */
    public function recordRefund(Payment $payment, User $by, string $reason): void
    {
        if ($payment->status !== TransactionStatus::Succeeded) {
            throw new RuntimeException('Seul un paiement réussi peut être remboursé.');
        }

        DB::transaction(function () use ($payment, $by, $reason): void {
            $payment->fill(['status' => TransactionStatus::Refunded, 'refunded_at' => now(), 'refunded_by' => $by->getKey(), 'refund_reason' => $reason]);
            $payment->record('refunded', ['by' => $by->getKey()]);
            $payment->save();

            $payment->order->update(['payment_status' => PaymentStatus::Refunded]);
        });

        activity()->causedBy($by)->performedOn($payment->order)->withProperties(['payment' => $payment->merchant_transaction_id, 'amount' => $payment->amount, 'reason' => $reason])->log('Remboursement enregistré');
    }

    private function markPaid(Payment $payment, PaymentState $state, string $source): Payment
    {
        $announce = false;
        $sold = false;

        DB::transaction(function () use ($payment, $state, $source, &$announce, &$sold): void {
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->first();

            if ($locked->status === TransactionStatus::Succeeded) {
                return; // Already applied by another channel (notification and return page may arrive together).
            }

            $locked->fill([
                'status' => TransactionStatus::Succeeded,
                'paid_at' => now(),
                'gateway_transaction_id' => $state->transactionId ?? $locked->gateway_transaction_id,
                'operator' => $state->operator ?? $locked->operator,
                'payer_phone' => $state->payerPhone ?? $locked->payer_phone,
                'failure_reason' => null,
            ]);
            $locked->record('succeeded', ['source' => $source, 'code' => $state->code, 'status' => $state->status]);
            $locked->save();

            $order = $locked->order;

            if ($order->payment_status === PaymentStatus::Pending) {
                $order->update(['payment_status' => PaymentStatus::Paid]);
                $announce = ! $order->trashed() && $order->status === OrderStatus::Received;
                $sold = true;
            } else {
                // Paid after the order was cancelled (e.g. past the timeout): the staff must refund it.
                Log::warning('[CinetPay] Payment received for an order no longer awaiting it', ['order' => $order->number, 'payment' => $locked->merchant_transaction_id]);
                activity()->performedOn($order)->withProperties(['payment' => $locked->merchant_transaction_id])->log('Paiement reçu sur une commande annulée : à rembourser');
            }
        });

        // The order is now a real order: the customer and the staff hear about it (F-130), once.
        if ($announce) {
            OrderPlaced::dispatch($payment->order->refresh());
        }

        // Meta hears of the sale from the server, whether the customer comes back from CinetPay or not.
        if ($sold) {
            app(MetaConversions::class)->purchase($payment->order);
        }

        return $payment->refresh();
    }

    private function markPending(Payment $payment, PaymentState $state, string $source): Payment
    {
        if ($payment->status !== TransactionStatus::Pending) {
            $payment->status = TransactionStatus::Pending;
        }

        $payment->record('pending', ['source' => $source, 'code' => $state->code, 'status' => $state->status]);
        $payment->save();

        return $payment;
    }

    private function close(Payment $payment, TransactionStatus $status, string $reason, ?PaymentState $state, string $source): Payment
    {
        $payment->status = $status;
        $payment->failure_reason = $reason;
        $payment->record($status->value, ['source' => $source, 'code' => $state?->code, 'status' => $state?->status]);
        $payment->save();

        return $payment;
    }

    /**
     * Our reference at CinetPay: unique, 30 characters at most ("KM" + date and time + 6 random characters).
     */
    private function reference(): string
    {
        return 'KM'.now()->format('ymdHis').Str::upper(Str::random(6));
    }

    /**
     * CinetPay wants a first and a last name of 2 characters at least.
     *
     * @return array{0: string, 1: string}
     */
    private function names(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = array_shift($parts) ?? '';
        $last = implode(' ', $parts);

        return [mb_strlen($first) >= 2 ? $first : 'Client', mb_strlen($last) >= 2 ? $last : config('storefront.name')];
    }
}
