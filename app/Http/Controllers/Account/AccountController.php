<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Account\AccountEraser;
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

        return view('pages.account.show', [
            'user' => $user,
            'phone' => $user->phone ? PhoneNumber::format($user->phone) : null,
            'recentOrders' => $user->orders()->withCount('items')->limit(3)->get(),
            'defaultAddress' => $user->defaultAddress()->with('commune')->first(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('pages.account.orders', [
            'orders' => $request->user()->orders()->with('items')->paginate(10),
        ]);
    }

    public function order(Request $request, Order $order): View
    {
        // Another customer's order does not exist, as far as this customer knows.
        abort_unless($order->user_id === $request->user()->getKey(), 404);

        return view('pages.account.order', ['order' => $order->load(['items', 'statusHistory'])]);
    }

    public function preferences(Request $request): RedirectResponse
    {
        $request->user()->update(['marketing_opt_in' => $request->boolean('marketing_opt_in')]);

        return back()->with('account_status', 'Vos préférences sont enregistrées.');
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
