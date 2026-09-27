<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\StockAlertsRelationManager;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\BackInStockForCustomer;
use App\Services\Catalog\StockManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class StockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_leaves_a_phone_or_an_email_on_a_sold_out_product(): void
    {
        $product = Product::factory()->create(['name' => 'Casque Bose', 'stock' => 0]);

        $this->alert($product, '07 01 02 03 04')->assertSessionHas('notice', 'C’est noté : nous vous prévenons dès que « Casque Bose » est de nouveau disponible.');
        $this->alert($product, 'Awa@Exemple.ci')->assertSessionHas('notice');
        // Asking twice keeps a single alert.
        $this->alert($product, '+225 0701020304');

        $this->assertSame([['+2250701020304', null], [null, 'awa@exemple.ci']], StockAlert::orderBy('id')->get()->map(fn ($alert) => [$alert->phone, $alert->email])->all());

        $this->alert($product, '12345')->assertSessionHas('notice_error', 'Indiquez un numéro à 10 chiffres (07 01 02 03 04) ou une adresse e-mail valide.');
        $this->assertSame(2, StockAlert::count());
    }

    public function test_an_available_product_needs_no_alert(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->alert($product, '0701020304')->assertSessionHas('notice', 'Bonne nouvelle : ce produit est disponible, vous pouvez le commander dès maintenant.');
        $this->assertSame(0, StockAlert::count());
    }

    public function test_the_restock_sends_the_alerts_once(): void
    {
        Notification::fake();
        $product = Product::factory()->create(['name' => 'Casque Bose', 'stock' => 0]);
        $this->alert($product, '0701020304');
        $this->alert($product, 'awa@exemple.ci');

        $stock = app(StockManager::class);
        $stock->adjust($product->defaultVariant, 3, StockMovementReason::Adjustment);
        $stock->adjust($product->defaultVariant, 2, StockMovementReason::Adjustment);

        $sent = collect(Notification::sentNotifications()[AnonymousNotifiable::class] ?? [])->flatten(2);
        $this->assertCount(2, $sent);
        Notification::assertSentOnDemand(BackInStockForCustomer::class, fn ($notification, array $channels, object $notifiable) => $notifiable->routes === ['sms' => '+2250701020304']
            && $notification->toSms($notifiable) === 'KOVA MARKET : « Casque Bose » est de nouveau disponible. '.$product->url());
        $this->assertSame(0, StockAlert::whereNull('notified_at')->count());
    }

    public function test_a_variant_alert_waits_for_that_variant(): void
    {
        Notification::fake();
        $product = Product::factory()->create(['name' => 'Coque', 'stock' => 0]);
        $color = ProductAttribute::create(['name' => 'Couleur', 'slug' => 'couleur']);
        $product->defaultVariant->attributeValues()->attach($color->values()->create(['value' => 'Noir']));
        $red = app(StockManager::class)->createVariant($product, ['sku' => 'COQUE-ROUGE', 'price' => 5000]);
        $red->attributeValues()->attach($color->values()->create(['value' => 'Rouge']));

        $this->post('/alertes-stock', ['product_id' => $product->id, 'variant_id' => $red->id, 'contact' => '0701020304']);

        app(StockManager::class)->adjust($product->defaultVariant, 4, StockMovementReason::Adjustment);
        Notification::assertNothingSent();

        app(StockManager::class)->adjust($red, 1, StockMovementReason::Adjustment);
        Notification::assertSentOnDemand(BackInStockForCustomer::class, fn ($notification, array $channels, object $notifiable) => str_contains($notification->toSms($notifiable), '« Coque (Couleur : Rouge) »'));
    }

    public function test_sold_out_products_offer_the_alert_on_their_page_and_card(): void
    {
        $product = Product::factory()->create(['name' => 'Montre GT', 'slug' => 'montre-gt', 'stock' => 0]);

        $this->get('/produit/montre-gt')->assertOk()
            ->assertSeeText('Épuisé : soyez prévenu de son retour')
            ->assertSee('action="'.route('stock-alerts.store').'"', false);

        $this->get('/boutique')->assertOk()
            ->assertSee('data-product-id="'.$product->id.'"', false)
            ->assertSee('data-stock-alert-name', false);
    }

    public function test_the_back_office_shows_who_is_waiting_for_a_product(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $awaited = Product::factory()->create(['name' => 'Casque Bose', 'stock' => 0]);
        $other = Product::factory()->create(['name' => 'Enceinte JBL', 'stock' => 0]);
        $this->alert($awaited, '0701020304');
        $this->alert($awaited, 'awa@exemple.ci');
        $sent = StockAlert::create(['product_id' => $awaited->id, 'email' => 'deja@exemple.ci']);
        $sent->forceFill(['notified_at' => now()])->save();

        $this->actingAs(User::factory()->staff(Role::Picker)->create());

        Livewire::test(ListProducts::class)
            ->assertTableColumnStateSet('waiting_alerts_count', 2, $awaited)
            ->filterTable('awaited')
            ->assertCanSeeTableRecords([$awaited])
            ->assertCanNotSeeTableRecords([$other]);

        $this->assertSame('2', StockAlertsRelationManager::getBadge($awaited, EditProduct::class));

        // Waiting alerts by default; a picker only reads them.
        Livewire::test(StockAlertsRelationManager::class, ['ownerRecord' => $awaited, 'pageClass' => EditProduct::class])
            ->assertCanSeeTableRecords(StockAlert::whereNull('notified_at')->get())
            ->assertCanNotSeeTableRecords([$sent])
            ->assertSeeText('07 01 02 03 04')
            ->assertActionHidden(TestAction::make('delete')->table(StockAlert::first()));

        // A catalog manager deletes an alert when the customer asks for it.
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        Livewire::test(StockAlertsRelationManager::class, ['ownerRecord' => $awaited, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('delete')->table(StockAlert::first()));

        $this->assertSame(1, $awaited->stockAlerts()->whereNull('notified_at')->count());
    }

    private function alert(Product $product, string $contact): TestResponse
    {
        return $this->post('/alertes-stock', ['product_id' => $product->id, 'contact' => $contact]);
    }
}
