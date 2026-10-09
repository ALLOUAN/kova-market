<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Models\Cart;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\ReviewInvitation;
use App\Services\Checkout\PlaceOrder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Product reviews: verified purchases only (a delivered order of the customer's account), published after moderation,
 * and the product's stars and count come from the published reviews only.
 */
class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Commune $cocody;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = DeliveryZone::create(['name' => 'Zone 1', 'fee' => 1500, 'is_active' => true]);
        $this->cocody = $zone->communes()->create(['name' => 'Cocody']);
        $this->customer = User::factory()->customer()->create(['name' => 'Aya Kouassi', 'phone' => '0701020304']);
        $this->product = Product::factory()->create(['name' => 'Enceinte JBL']);
    }

    public function test_a_product_without_published_review_shows_no_stars(): void
    {
        $this->assertSame(0, $this->product->fresh()->reviews_count);

        $this->get($this->product->url())->assertOk()->assertDontSee('rbt-rating-icon-list', false)->assertDontSeeText('Avis clients');
    }

    public function test_the_customer_reviews_a_delivered_article_then_the_back_office_publishes_it(): void
    {
        $order = $this->deliveredOrder();
        $item = $order->items->first();

        $this->actingAs($this->customer)->get(route('account.orders.show', $order))->assertSeeText('Votre avis sur vos articles');

        $this->post(route('account.reviews.store', [$order, $item]), ['rating' => 4, 'comment' => 'Bon son, livraison rapide.'])
            ->assertSessionHas('account_status');

        $review = ProductReview::sole();
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame('Aya K.', $review->author_name);
        // Not published yet: nothing on the product.
        $this->assertSame(0, $this->product->fresh()->reviews_count);
        $this->get($this->product->url())->assertDontSeeText('Bon son, livraison rapide.');

        // One review per article.
        $this->post(route('account.reviews.store', [$order, $item]), ['rating' => 5])->assertSessionHas('account_error');
        $this->assertSame(1, ProductReview::count());

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        $this->assertSame('1', ProductReviewResource::getNavigationBadge());
        Livewire::test(ListProductReviews::class)
            ->assertCanSeeTableRecords([$review])
            ->callAction(TestAction::make('approve')->table($review));

        $product = $this->product->fresh();
        $this->assertSame(1, $product->reviews_count);
        $this->assertSame(4.0, $product->rating);

        $this->get($product->url())
            ->assertSeeText('Avis clients')
            ->assertSeeText('Bon son, livraison rapide.')
            ->assertSeeText('Aya K.')
            ->assertSeeText('Achat vérifié')
            ->assertSee('"aggregateRating"', false);

        // Refused afterwards: back to no stars.
        Livewire::test(ListProductReviews::class)->set('activeTab', 'approved')->callAction(TestAction::make('reject')->table($review->fresh()));
        $this->assertSame(0, $this->product->fresh()->reviews_count);
    }

    public function test_only_the_customer_of_a_delivered_order_can_review(): void
    {
        $order = $this->deliveredOrder();
        $item = $order->items->first();

        // Someone else.
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('account.reviews.store', [$order, $item]), ['rating' => 5])->assertNotFound();

        // Not delivered yet.
        $order->forceFill(['status' => OrderStatus::Preparing])->save();
        $this->actingAs($this->customer)
            ->post(route('account.reviews.store', [$order, $item]), ['rating' => 5])->assertSessionHas('account_error');

        // A rating is required.
        $order->forceFill(['status' => OrderStatus::Delivered])->save();
        $this->post(route('account.reviews.store', [$order, $item]), ['rating' => 9])->assertSessionHasErrors('rating');

        $this->assertSame(0, ProductReview::count());
    }

    public function test_catalog_viewers_see_reviews_without_moderating(): void
    {
        $order = $this->deliveredOrder();
        $review = ProductReview::create(['product_id' => $this->product->id, 'order_item_id' => $order->items->first()->id, 'author_name' => 'Aya K.', 'rating' => 5, 'status' => ReviewStatus::Pending]);

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        $this->get(ProductReviewResource::getUrl('index'))->assertOk();
        Livewire::test(ListProductReviews::class)->assertActionHidden(TestAction::make('approve')->table($review));
    }

    public function test_customers_are_invited_to_review_two_days_after_the_delivery_once(): void
    {
        Notification::fake();
        $this->customer->update(['email' => 'aya@example.ci']);
        $order = $this->deliveredOrder();
        $deliveredAt = $order->statusHistory()->create(['from_status' => OrderStatus::OutForDelivery, 'to_status' => OrderStatus::Delivered]);

        // Delivered today: too early.
        $this->artisan('reviews:invite')->expectsOutputToContain('0 invitation');
        Notification::assertNotSentTo($this->customer, ReviewInvitation::class);

        $deliveredAt->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->artisan('reviews:invite')->expectsOutputToContain('1 invitation');
        $this->artisan('reviews:invite')->expectsOutputToContain('0 invitation');

        Notification::assertSentToTimes($this->customer, ReviewInvitation::class, 1);
        Notification::assertSentTo($this->customer, ReviewInvitation::class, function (ReviewInvitation $invitation) use ($order) {
            $mail = $invitation->toMail($this->customer);

            return $mail->actionUrl === route('account.orders.show', $order) && in_array('• Enceinte JBL', $mail->introLines, true);
        });
        $this->assertNotNull($order->fresh()->review_invited_at);
    }

    public function test_an_order_already_reviewed_is_not_invited(): void
    {
        Notification::fake();
        $this->customer->update(['email' => 'aya@example.ci']);
        $order = $this->deliveredOrder();
        $order->statusHistory()->create(['from_status' => OrderStatus::OutForDelivery, 'to_status' => OrderStatus::Delivered])
            ->forceFill(['created_at' => now()->subDays(3)])->save();
        ProductReview::create(['product_id' => $this->product->id, 'user_id' => $this->customer->id, 'order_item_id' => $order->items->first()->id,
            'author_name' => 'Aya K.', 'rating' => 5, 'status' => ReviewStatus::Pending]);

        $this->artisan('reviews:invite')->expectsOutputToContain('0 invitation');
        Notification::assertNotSentTo($this->customer, ReviewInvitation::class);
    }

    private function deliveredOrder(): Order
    {
        $cart = Cart::create(['token' => fake()->uuid(), 'expires_at' => now()->addDay()]);
        $cart->items()->create(['product_variant_id' => $this->product->defaultVariant->id, 'quantity' => 1]);

        $order = app(PlaceOrder::class)->handle($cart, [
            'customer_name' => $this->customer->name, 'phone' => $this->customer->phone, 'email' => null, 'commune_id' => $this->cocody->id,
            'district' => 'Riviera', 'landmark' => null, 'note' => null,
            'payment_method' => PaymentMethod::CashOnDelivery->value, 'marketing_opt_in' => false,
        ], $this->customer);

        $order->forceFill(['status' => OrderStatus::Delivered])->save();

        return $order->load('items');
    }
}
