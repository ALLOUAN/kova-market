<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Address book through the API (F-071): several addresses, one default. Another customer's address is a 404.
 */
class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection($request->user()->addresses()->with('commune.zone')->get());
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->canAddAddress()) {
            throw ValidationException::withMessages(['label' => 'Vous avez atteint le nombre maximum d’adresses ('.Address::MAX_PER_CUSTOMER.').']);
        }

        $data = $request->details();
        // The first address is the default one.
        $data['is_default'] = $data['is_default'] || $user->addresses()->doesntExist();

        return AddressResource::make($user->addresses()->create($data)->load('commune.zone'))->response()->setStatusCode(201);
    }

    public function update(AddressRequest $request, Address $address): AddressResource
    {
        $this->authorizeOwner($request, $address);

        $data = $request->details();
        $data['is_default'] = $data['is_default'] || $address->is_default;
        $address->update($data);

        return AddressResource::make($address->load('commune.zone'));
    }

    public function makeDefault(Request $request, Address $address): AddressResource
    {
        $this->authorizeOwner($request, $address);
        $address->update(['is_default' => true]);

        return AddressResource::make($address->load('commune.zone'));
    }

    public function destroy(Request $request, Address $address): Response
    {
        $this->authorizeOwner($request, $address);
        $address->delete();

        return response()->noContent();
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->getKey(), 404);
    }
}
