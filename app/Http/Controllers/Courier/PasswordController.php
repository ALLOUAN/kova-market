<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
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
        return view('courier.password', ['mustChange' => $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['password' => 'mot de passe']);

        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect()->route('courier.home')->with('courier_status', 'Votre mot de passe est enregistré.');
    }
}
