<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The customer's receipt (PDF) of an order: opened from the signed link of the e-mails and of the tracking page,
 * or by whoever placed the order (this browser, or the account it belongs to).
 */
class ReceiptController extends Controller
{
    public function __invoke(Request $request, Order $order, OrderReceipt $receipt): Response
    {
        // Another order, as far as this visitor knows, does not exist.
        abort_unless($request->hasValidSignature() || CheckoutController::isVisibleTo($request, $order), 404);

        return response($receipt->pdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$receipt->filename($order).'"',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
