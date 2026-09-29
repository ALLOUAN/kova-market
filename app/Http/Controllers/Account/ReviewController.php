<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Product reviews by customers (verified purchases): from a delivered order of their account, one review per order
 * line, published once the back-office approves it.
 */
class ReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        // Another customer's order, or a line of another order, does not exist as far as this customer knows.
        abort_unless($order->user_id === $request->user()->getKey() && $item->order_id === $order->getKey(), 404);

        if ($order->status !== OrderStatus::Delivered || $item->product_id === null) {
            return back()->with('account_error', 'Vous pourrez donner votre avis une fois la commande livrée.');
        }

        if (ProductReview::where('order_item_id', $item->getKey())->exists()) {
            return back()->with('account_error', 'Vous avez déjà donné votre avis sur cet article.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Choisissez une note de 1 à 5 étoiles.',
        ], ['rating' => 'note', 'comment' => 'commentaire']);

        ProductReview::create([
            'product_id' => $item->product_id,
            'user_id' => $request->user()->getKey(),
            'order_item_id' => $item->getKey(),
            'author_name' => self::displayName($request->user()->name),
            'rating' => $data['rating'],
            'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            'status' => ReviewStatus::Pending,
        ]);

        return back()->with('account_status', 'Merci ! Votre avis sera publié après vérification.');
    }

    /**
     * "Awa Koné" is shown "Awa K.": enough to be real, not enough to expose the customer.
     */
    public static function displayName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = Str::limit($parts[0] ?? 'Client', 40, '');

        return isset($parts[1]) ? $first.' '.Str::upper(Str::substr($parts[1], 0, 1)).'.' : $first;
    }
}
