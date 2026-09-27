<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public order tracking by order number + phone number (F-073). Shows the progress, never the address
 * details or the e-mail; a wrong pair always gets the same answer.
 */
class OrderTrackingController extends Controller
{
    public function show(): View
    {
        return view('pages.tracking', ['order' => null]);
    }

    public function search(Request $request): View
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ], [], ['number' => 'numéro de commande', 'phone' => 'téléphone']);

        $order = Order::query()
            ->where('number', strtoupper(trim($data['number'])))
            ->where('phone', PhoneNumber::normalize($data['phone']) ?? '')
            ->with(['items', 'statusHistory'])
            ->first();

        return view('pages.tracking', [
            'order' => $order,
            'notFound' => $order === null,
        ]);
    }
}
