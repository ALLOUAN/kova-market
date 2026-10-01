<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use App\Services\Account\AccountEraser;
use App\Services\Account\GuestOrderClaim;
use App\Services\Security\SmsCode;
use App\Services\Storefront\Wishlist;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer area (F-070 to F-075): profile, orders, preferences and personal data.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $statuses = $user->orders()->reorder()->pluck('status');

        return view('pages.account.show', [
            'user' => $user,
            'phone' => $user->phone ? PhoneNumber::format($user->phone) : null,
            'recentOrders' => $user->orders()->withCount('items')->limit(3)->get(),
            // The figures of the dashboard tiles.
            'stats' => [
                'orders' => $statuses->count(),
                'open' => $statuses->reject(fn (OrderStatus $status) => $status->isFinal())->count(),
                'addresses' => $user->addresses()->count(),
                'wishlist' => config('storefront.features.wishlist') ? app(Wishlist::class)->count() : null,
            ],
            'defaultAddress' => $user->defaultAddress()->with('commune')->first(),
            'guestOrdersCount' => app(GuestOrderClaim::class)->pending($user)->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('pages.account.orders', [
            'orders' => $request->user()->orders()->with('items')->paginate(10),
        ]);
    }

    /**
     * The orders still on their way, with their progress: "Suivre une commande" inside the customer area.
     */
    public function tracking(Request $request): View
    {
        $orders = $request->user()->orders()->with(['statusHistory', 'courier.user'])->withCount('items')->get()
            ->reject(fn (Order $order) => $order->status->isFinal())
            ->values();

        return view('pages.account.tracking', ['orders' => $orders]);
    }

    /**
     * The favourites inside the customer area (the storefront's /favoris page stays for visitors).
     */
    public function wishlist(Wishlist $wishlist): View
    {
        abort_unless(config('storefront.features.wishlist'), 404);

        return view('pages.account.wishlist', ['products' => $wishlist->products()]);
    }

    public function order(Request $request, Order $order): View
    {
        // Another customer's order does not exist, as far as this customer knows.
        abort_unless($order->user_id === $request->user()->getKey(), 404);

        return view('pages.account.order', [
            'order' => $order->load(['items', 'statusHistory', 'courier.user']),
            // Reviews already given on this order's lines, by line.
            'reviews' => ProductReview::whereIn('order_item_id', $order->items->modelKeys())->get()->keyBy('order_item_id'),
        ]);
    }

    /**
     * "Retrouver mes commandes" (F-070): sends the SMS code proving the phone number is the customer's.
     */
    public function claimGuestOrders(Request $request, GuestOrderClaim $claim): RedirectResponse
    {
        $claim->sendCode($request->user());

        return back()->with('claim_code_sent', true)->with('account_status', 'Un code vient de vous être envoyé par '.SmsCode::channelLabel().' au '.PhoneNumber::format($request->user()->phone).'.');
    }

    public function confirmGuestOrders(Request $request, GuestOrderClaim $claim): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']], [], ['code' => 'code']);
        $count = $claim->confirm($request->user(), $data['code']);

        if ($count === null) {
            return back()->with('claim_code_sent', true)->withErrors(['code' => 'Ce code n’est pas valide ou a expiré. Demandez un nouveau code.'], 'claim');
        }

        return redirect()->route('account.orders')->with('account_status', "{$count} commande(s) ajoutée(s) à votre compte.");
    }

    public function preferences(Request $request): RedirectResponse
    {
        $request->user()->update(['marketing_opt_in' => $request->boolean('marketing_opt_in')]);

        return back()->with('account_status', 'Vos préférences sont enregistrées.')->with('account_tab', 'preferences');
    }

    public function export(Request $request, AccountEraser $eraser): StreamedResponse
    {
        $data = json_encode($eraser->export($request->user()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print ($data), 'mes-donnees-kova-market.json', ['Content-Type' => 'application/json']);
    }

    public function destroy(Request $request, AccountEraser $eraser): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.'], 'deleteAccount');
        }

        try {
            $eraser->erase($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['password' => $exception->getMessage()], 'deleteAccount');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('cart_status', 'Votre compte a été supprimé. Vos données personnelles ont été effacées.');
    }
}
