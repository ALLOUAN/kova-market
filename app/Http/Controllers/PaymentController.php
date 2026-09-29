<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\CinetPayException;
use App\Services\Payments\OnlinePayments;
use App\Services\Storefront\Analytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The customer's side of online payment (F-060 to F-066): (re)trying the payment of an order, coming back from
 * CinetPay, and CinetPay's server-to-server notification. Every outcome is checked with CinetPay's API.
 */
class PaymentController extends Controller
{
    public function __construct(private OnlinePayments $payments) {}

    /**
     * "Payer maintenant": a new payment attempt for an order still waiting for it.
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        abort_unless(CheckoutController::isVisibleTo($request, $order), 404);

        if (! $order->awaitsOnlinePayment()) {
            return redirect()->route('checkout.confirmation', $order);
        }

        try {
            return redirect()->away($this->payments->start($order)->payment_url);
        } catch (CinetPayException $exception) {
            return redirect()->route('checkout.confirmation', $order)->with('payment_error', $exception->getMessage());
        }
    }

    /**
     * success_url and failed_url of CinetPay: the page the customer comes back to, whatever happened there.
     * The outcome comes from CinetPay's status check, not from the URL. The customer who placed the order goes on to
     * its confirmation; anyone else (another browser after the operator's app) sees the outcome only.
     */
    public function back(Request $request, Payment $payment, Analytics $analytics): RedirectResponse|View
    {
        try {
            $payment = $this->payments->synchronize($payment, 'return');
        } catch (CinetPayException) {
            // CinetPay does not answer now: the notification or the next check will settle it.
        }

        $order = $payment->order;
        $message = match ($payment->status) {
            TransactionStatus::Succeeded => ['payment_status', 'Paiement reçu, merci ! Votre commande est enregistrée.'],
            TransactionStatus::Failed, TransactionStatus::Cancelled => ['payment_error', 'Le paiement n’a pas abouti. Vous pouvez réessayer.'],
            default => ['payment_status', 'Votre paiement est en cours de confirmation par CinetPay. Cette page se met à jour dès qu’il est confirmé.'],
        };

        if (CheckoutController::isVisibleTo($request, $order)) {
            if ($payment->status === TransactionStatus::Succeeded && ! $request->session()->has("purchase_reported.{$order->number}")) {
                $request->session()->put("purchase_reported.{$order->number}", true);
                $analytics->purchase($order->load('items'));
            }

            return redirect()->route('checkout.confirmation', $order)->with($message[0], $message[1]);
        }

        return view('pages.payment-result', ['payment' => $payment, 'order' => $order]);
    }

    /**
     * notify_url of CinetPay. Always answers 200 (otherwise CinetPay retries without end); a GET is an availability
     * check. The body only names the payment: its outcome is fetched from CinetPay.
     */
    public function notify(Request $request): Response
    {
        if ($request->isMethod('post')) {
            $this->payments->handleNotification($request->json()->all() ?: $request->all());
        }

        return response('OK', 200);
    }
}
