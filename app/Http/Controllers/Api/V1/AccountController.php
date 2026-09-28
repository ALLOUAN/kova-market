<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Order;
use App\Services\Account\AccountEraser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Customer account through the API (F-072, F-074, F-075): profile, orders, preferences and personal data.
 */
class AccountController extends Controller
{
    public function show(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function preferences(Request $request): UserResource
    {
        $data = $request->validate(['marketing_opt_in' => ['required', 'boolean']]);
        $request->user()->update(['marketing_opt_in' => (bool) $data['marketing_opt_in']]);

        return UserResource::make($request->user());
    }

    public function orders(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($request->user()->orders()->with(['items', 'statusHistory', 'courier.user'])->paginate(10));
    }

    public function order(Request $request, Order $order): OrderResource
    {
        // Another customer's order does not exist, as far as this customer knows.
        abort_unless($order->user_id === $request->user()->getKey(), 404);

        return OrderResource::make($order->load(['items', 'statusHistory', 'courier.user']));
    }

    public function export(Request $request, AccountEraser $eraser): JsonResponse
    {
        return response()->json($eraser->export($request->user()));
    }

    public function destroy(Request $request, AccountEraser $eraser): Response
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        try {
            $eraser->erase($user);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['password' => $exception->getMessage()]);
        }

        $user->tokens()->delete();

        return response()->noContent();
    }
}
