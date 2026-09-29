<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Storefront\Comparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Product comparison: up to four products chosen from their cards or pages, the bottom bar, the side-by-side page.
 */
class CompareTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_chosen_then_compared_side_by_side(): void
    {
        $phone = Product::factory()->create(['name' => 'Téléphone A', 'specifications' => [['label' => 'Écran', 'value' => '6,5 pouces'], ['label' => 'Stockage', 'value' => '128 Go']]]);
        $other = Product::factory()->create(['name' => 'Téléphone B', 'specifications' => [['label' => 'Stockage', 'value' => '256 Go'], ['label' => 'Batterie', 'value' => '5000 mAh']]]);

        $this->get('/')->assertSee('action="'.route('compare.toggle', $phone).'"', false);

        $this->postJson(route('compare.toggle', $phone))->assertOk()->assertJson(['added' => true, 'count' => 1, 'ids' => [$phone->id]])
            ->assertJsonPath('bar', fn (string $bar) => str_contains($bar, 'Ajoutez un autre produit pour comparer'));
        $this->postJson(route('compare.toggle', $other))->assertJson(['added' => true, 'count' => 2])
            ->assertJsonPath('bar', fn (string $bar) => str_contains($bar, 'href="'.route('compare.index').'"'));

        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSeeInOrder(['Téléphone A', 'Téléphone B'])
            // The specifications of both, one row per label, a dash when a product has none.
            ->assertSeeInOrder(['Écran', '6,5 pouces', '—'])
            ->assertSeeInOrder(['Stockage', '128 Go', '256 Go'])
            ->assertSeeInOrder(['Batterie', '—', '5000 mAh']);

        // Removing one, then emptying.
        $this->postJson(route('compare.toggle', $phone))->assertJson(['added' => false, 'count' => 1]);
        $this->deleteJson(route('compare.clear'))->assertJson(['count' => 0]);
        $this->get(route('compare.index'))->assertSeeText('Aucun produit à comparer');
    }

    public function test_no_more_than_four_products_are_compared(): void
    {
        $products = Product::factory()->count(Comparison::MAX + 1)->create();

        foreach ($products->take(Comparison::MAX) as $product) {
            $this->postJson(route('compare.toggle', $product))->assertOk();
        }

        $this->postJson(route('compare.toggle', $products->last()))
            ->assertStatus(422)
            ->assertJson(['added' => null, 'count' => Comparison::MAX, 'message' => 'Vous comparez déjà 4 produits : retirez-en un pour en ajouter un autre.']);
    }

    public function test_without_javascript_the_buttons_still_work(): void
    {
        $product = Product::factory()->create(['name' => 'Enceinte']);

        $this->from($product->url())->post(route('compare.toggle', $product))->assertRedirect($product->url())->assertSessionHas('cart_status');
        $this->get($product->url())->assertSee('Dans le comparateur', false)->assertSee('data-compare-bar', false);
    }
}
