<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Customer sign-up, sign-in and sign-out for API clients (F-160, F-070): same rules as the storefront, a Sanctum
 * token instead of a session. A guest cart sent in X-Cart-Token joins the account, as at a storefront sign-in.
 */
class AuthController extends Controller
{
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

        // A suspended account is refused like a wrong password.
        if (! $user || $user->isSuspended() || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        return $this->tokenResponse($user, $request->input('device_name'), $cart);
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
