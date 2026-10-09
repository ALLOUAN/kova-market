<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Services\Storefront\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The courier replaces the temporary password received by SMS (F-122), or changes it later.
 */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('courier.password', [
            'mustChange' => $user->must_change_password,
            'courier' => $user->courier->load('zones'),
            'contact' => app(StoreSettings::class)->contact(),
            'storeWhatsapp' => app(StoreSettings::class)->whatsappUrl('Bonjour, je suis le livreur '.$user->name.'.'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // A later change asks for the current password (F-147); the first one follows the sign-in with the
        // temporary password just received.
        $data = $request->validate([
            'current_password' => $request->user()->must_change_password ? ['nullable'] : ['required', 'string', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['current_password.current_password' => 'Mot de passe actuel incorrect.'], ['password' => 'mot de passe', 'current_password' => 'mot de passe actuel']);

        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect()->route('courier.home')->with('courier_status', 'Votre mot de passe est enregistré.');
    }
}
