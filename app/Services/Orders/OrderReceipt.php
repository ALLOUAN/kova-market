<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;

/**
 * The customer's receipt of an order (PDF): "Reçu de commande" while payment is due, "Reçu de paiement" once paid
 * (online through CinetPay, or cash on delivery). Given on the confirmation page, in the customer area, on the
 * tracking page and with the order e-mails; its link is signed, so it opens from an e-mail without signing in.
 */
class OrderReceipt
{
    public function pdf(Order $order): string
    {
        return Pdf::loadHTML($this->html($order))->setPaper('a4')->output();
    }

    /**
     * The receipt before it becomes a PDF.
     */
    public function html(Order $order): string
    {
        $order->loadMissing(['items', 'payments', 'statusHistory']);

        return view('pdf.receipt', [
            'order' => $order,
            'paid' => $order->payment_status === PaymentStatus::Paid || $order->payment_status === PaymentStatus::Refunded,
            'paidAt' => $this->paidAt($order),
            'payment' => $order->payments->firstWhere('status', TransactionStatus::Succeeded) ?? $order->payments->firstWhere('status', TransactionStatus::Refunded),
        ])->render();
    }

    public function filename(Order $order): string
    {
        return "recu-{$order->number}.pdf";
    }

    /**
     * A link that opens the receipt from anywhere (e-mail, SMS), without an account.
     */
    public function url(Order $order): string
    {
        return URL::signedRoute('orders.receipt', ['order' => $order->number]);
    }

    /**
     * When the order was paid: the CinetPay payment, or the delivery for cash on delivery.
     */
    public function paidAt(Order $order): ?CarbonInterface
    {
        if ($order->payment_status !== PaymentStatus::Paid && $order->payment_status !== PaymentStatus::Refunded) {
            return null;
        }

        $online = $order->payments->whereNotNull('paid_at')->sortBy('paid_at')->first();

        return $online?->paid_at
            ?? $order->statusHistory->firstWhere('to_status', OrderStatus::Delivered)?->created_at
            ?? $order->updated_at;
    }
}
