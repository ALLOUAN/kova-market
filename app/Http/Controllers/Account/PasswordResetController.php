<?php

namespace App\Http\Controllers\Account;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Rules\PassesTurnstile;
use App\Services\Account\PasswordRecovery;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Mot de passe oublié" pages (F-076): the customer types a phone number (code by SMS) or an e-mail address
 * (link by e-mail), then chooses a new password. The answers never reveal whether an account exists.
 */
class PasswordResetController extends Controller
{
    use PasswordValidationRules;

    /** Session key holding the phone number the code was asked for. */
    private const PHONE = 'password_reset_phone';

    public function __construct(private PasswordRecovery $recovery) {}

    public function create(): View
    {
        return view('pages.password.request');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'cf-turnstile-response' => [new PassesTurnstile],
        ], ['login.required' => 'Indiquez votre numéro de téléphone ou votre e-mail.']);

        $login = trim($data['login']);

        if (str_contains($login, '@')) {
            $this->recovery->request($login);

            return back()->with('status', 'Si un compte correspond à cette adresse, vous allez recevoir un e-mail avec un lien pour choisir un nouveau mot de passe. Il est valable 60 minutes.');
        }

        if (! PhoneNumber::isValid($login)) {
            return back()->withInput()->withErrors(['login' => 'Indiquez un numéro à 10 chiffres (07 01 02 03 04) ou une adresse e-mail valide.']);
        }

        $this->recovery->request($login);
        $request->session()->put(self::PHONE, PhoneNumber::normalize($login));

        return redirect()->route('password.code');
    }

    public function editWithCode(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::PHONE);

        return $phone
            ? view('pages.password.code', ['phone' => PhoneNumber::format($phone)])
            : redirect()->route('password.request');
    }

    public function updateWithCode(Request $request): RedirectResponse
    {
        $phone = $request->session()->get(self::PHONE) ?? abort(404);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10'],
            'password' => $this->passwordRules(),
        ], [], ['code' => 'code']);

        if (! $this->recovery->resetWithCode($phone, $data['code'], $request->only(['password', 'password_confirmation']))) {
            return back()->withErrors(['code' => 'Ce code n’est pas valide ou a expiré. Vérifiez le SMS reçu, ou demandez un nouveau code.']);
        }

        $request->session()->forget(self::PHONE);

        return $this->signInInvitation();
    }

    public function editWithToken(Request $request, string $token): View
    {
        return view('pages.password.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function updateWithToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->passwordRules(),
        ]);

        $status = $this->recovery->resetWithToken([...$data, 'password_confirmation' => $request->input('password_confirmation')]);

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Ce lien n’est plus valable : il a déjà servi ou a expiré. Demandez-en un nouveau.']);
        }

        return $this->signInInvitation();
    }

    /**
     * Back to the home page with the sign-in form open.
     */
    private function signInInvitation(): RedirectResponse
    {
        return redirect()->route('home', ['connexion' => 1])->with('cart_status', 'Votre mot de passe a été changé. Connectez-vous avec le nouveau.');
    }
}
