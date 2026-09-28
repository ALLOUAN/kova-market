<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_signs_up_and_uses_the_token(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koné',
            'phone' => '07 01 02 03 04',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_name' => 'Pixel 8',
        ])
            ->assertCreated()
            ->assertJsonPath('user.phone', '+2250701020304')
            ->assertJsonPath('user.email', null)
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/account')->assertOk()->assertJsonPath('data.name', 'Awa Koné');
    }

    public function test_sign_up_follows_the_storefront_rules(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koné',
            'phone' => '+225 07 01 02 03 04',
            'password' => 'short',
            'password_confirmation' => 'short',
            'device_name' => 'Pixel 8',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'Un compte existe déjà avec ce numéro de téléphone.'])
            ->assertJsonValidationErrors('password');
    }

    public function test_a_customer_signs_in_with_the_phone_typed_any_way(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);

        $this->postJson('/api/v1/auth/login', ['login' => '+225 07 01 02 03 04', 'password' => 'password', 'device_name' => 'iPhone'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'phone']]);

        $this->postJson('/api/v1/auth/login', ['login' => '0701020304', 'password' => 'wrong', 'device_name' => 'iPhone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }

    public function test_a_suspended_account_cannot_sign_in_nor_use_its_tokens(): void
    {
        $user = User::factory()->customer()->create(['phone' => '0701020304']);
        $token = $user->createToken('iPhone')->plainTextToken;
        $user->forceFill(['suspended_at' => now()])->save();

        $this->postJson('/api/v1/auth/login', ['login' => '0701020304', 'password' => 'password', 'device_name' => 'iPhone'])
            ->assertUnprocessable();

        $this->withToken($token)->getJson('/api/v1/account')->assertUnauthorized();
    }

    public function test_sign_out_revokes_the_token(): void
    {
        $user = User::factory()->customer()->create();
        $token = $user->createToken('iPhone')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/account')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_account_routes_need_a_token(): void
    {
        $this->getJson('/api/v1/account')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->getJson('/api/v1/account/orders')->assertUnauthorized();
    }

    public function test_the_guest_cart_joins_the_account_at_sign_in(): void
    {
        User::factory()->customer()->create(['phone' => '0701020304']);
        $product = Product::factory()->create(['stock' => 5]);
        $cartToken = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->json('data.token');

        $token = $this->postJson('/api/v1/auth/login', ['login' => '0701020304', 'password' => 'password', 'device_name' => 'iPhone'], [CartManager::HEADER => $cartToken])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 2)
            // A customer's cart is found through the account: no token to keep.
            ->assertJsonPath('data.token', null);
        $this->assertNotNull(Cart::sole()->user_id);
    }
}
