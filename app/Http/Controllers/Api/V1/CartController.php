<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Commune;
use App\Models\Coupon;
use App\Services\Cart\CartManager;
use App\Services\Promotions\CouponException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The cart through the API (F-040 to F-044). Every change answers with the whole cart; refusals (sold out,
 * commune not served, promo code) come back as 422 with their message.
 */
class CartController extends Controller
{
    public function __construct(private CartManager $cart) {}

    public function show(): CartResource
    {
        return $this->cartResource();
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $this->cart->add($request->variant(), $request->quantity());

        return $this->cartResource()->response()->setStatusCode(201);
    }

    public function update(Request $request, int $item): CartResource
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartManager::MAX_QUANTITY]]);

        $this->cart->update($this->cart->findItem($item) ?? abort(404), (int) $data['quantity']);

        return $this->cartResource();
    }

    public function destroy(int $item): CartResource
    {
        $this->cart->remove($this->cart->findItem($item) ?? abort(404));

        return $this->cartResource();
    }

    public function commune(Request $request): CartResource
    {
        $data = $request->validate(['commune_id' => ['required', 'integer', 'exists:communes,id']], [], ['commune_id' => 'commune']);

        $this->cart->chooseCommune(Commune::with('zone')->findOrFail($data['commune_id']));

        return $this->cartResource();
    }

    public function applyCoupon(Request $request): CartResource
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => 'code promo']);

        $this->cart->applyCoupon(Coupon::findByCode($data['code']) ?? throw new CouponException('Ce code promo n’existe pas.'));

        return $this->cartResource();
    }

    public function removeCoupon(): CartResource
    {
        $this->cart->removeCoupon();

        return $this->cartResource();
    }

    private function cartResource(): CartResource
    {
        return CartResource::make($this->cart->summary());
    }
}
