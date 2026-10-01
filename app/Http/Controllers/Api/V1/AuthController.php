<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Account\PasswordRecovery;
use App\Services\Cart\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Customer sign-up, sign-in and sign-out for API clients (F-160, F-070): same rules as the storefront, a Sanctum
 * token instead of a session. A guest cart sent in X-Cart-Token joins the account, as at a storefront sign-in.
 */
class AuthController extends Controller
{
    use PasswordValidationRules;

    public function register(Request $request, CreateNewUser $creator, CartManager $cart): JsonResponse
    {
        $request->validate(['device_name' => ['required', 'string', 'max:100']]);

        $user = $creator->create($request->only(['name', 'phone', 'email', 'password', 'password_confirmation']));

        return $this->tokenResponse($user, $request->input('device_name'), $cart, 201);
    }

    public function login(Request $request, CartManager $cart): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::findByLogin($request->input('login'));

        // Customers only (the team signs in on the back-office, with its two-factor code); a team account or a
        // suspended one is refused like a wrong password (F-147).
        if (! User::passwordMatches($user, $request->input('password')) || ! $user->isCustomer() || $user->isSuspended()) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        return $this->tokenResponse($user, $request->input('device_name'), $cart);
    }

    /**
     * "Mot de passe oublié" (F-076): a code by SMS for a phone number, a link by e-mail for an address. Same answer
     * whether or not an account exists.
     */
    public function forgotPassword(Request $request, PasswordRecovery $recovery): JsonResponse
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255']]);

        $recovery->request(trim($data['login']));

        return response()->json(['message' => 'Si un compte correspond, un code vous a été envoyé par SMS ou un lien par e-mail.'], 202);
    }

    public function resetPassword(Request $request, PasswordRecovery $recovery): Response
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:10'],
            'password' => $this->passwordRules(),
        ]);

        if (! $recovery->resetWithCode($data['phone'], $data['code'], $request->only(['password', 'password_confirmation']))) {
            throw ValidationException::withMessages(['code' => 'Ce code n’est pas valide ou a expiré. Demandez un nouveau code.']);
        }

        return response()->noContent();
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, string $device, CartManager $cart, int $status = 200): JsonResponse
    {
        $cart->mergeGuestCartInto($user);

        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'user' => UserResource::make($user),
        ], $status);
    }
}
