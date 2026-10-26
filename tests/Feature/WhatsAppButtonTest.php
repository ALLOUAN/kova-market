<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\WhatsAppButton;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Contenus › Bouton WhatsApp: the floating button and its chat window, set up in the back-office.
 */
class WhatsAppButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chat_window_shows_what_the_back_office_set(): void
    {
        Setting::store([
            'contact.whatsapp' => '2250700000000',
            'whatsapp_widget.agent_name' => 'Awa, service client',
            'whatsapp_widget.greeting' => 'Bonjour 👋 Une question sur une commande ?',
            'whatsapp_widget.button_label' => 'Écrire à Awa',
            'whatsapp_widget.position' => 'right',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('wa-widget wa-right', false)
            ->assertSeeText('Awa, service client')
            ->assertSeeText('Bonjour 👋 Une question sur une commande ?')
            ->assertSeeText('Écrire à Awa');
    }

    public function test_without_the_window_the_button_goes_straight_to_whatsapp(): void
    {
        Setting::store(['contact.whatsapp' => '2250700000000', 'whatsapp_widget.greeting_enabled' => '0', 'whatsapp_widget.include_page' => '0']);

        $this->get('/')->assertOk()
            ->assertDontSee('data-wa-card', false)
            ->assertDontSee('data-wa-toggle', false)
            ->assertSee('https://wa.me/2250700000000?text='.rawurlencode('Bonjour KOVA MARKET, j’ai une question.').'"', false);
    }

    public function test_the_button_hides_when_switched_off_or_during_the_checkout(): void
    {
        Setting::store(['contact.whatsapp' => '2250700000000']);
        // The order form needs something in the cart.
        $this->post('/panier/articles', ['variant_id' => Product::factory()->create(['stock' => 5])->defaultVariant->id, 'quantity' => 1]);
        $this->withCookie(CartManager::COOKIE, Cart::sole()->token);

        $this->get('/commande')->assertOk()->assertDontSee('data-wa-widget', false);
        $this->get('/contact')->assertSee('data-wa-widget', false);

        Setting::store(['whatsapp_widget.hide_on_checkout' => '0']);
        $this->get('/commande')->assertSee('data-wa-widget', false);

        Setting::store(['whatsapp_widget.enabled' => '0']);
        $this->get('/contact')->assertDontSee('data-wa-widget', false);
    }

    public function test_the_back_office_saves_the_button_and_the_shared_number(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(WhatsAppButton::class)
            ->assertSeeText('Aperçu')
            ->fillForm([
                'phone' => '2250102030405',
                'message' => 'Bonjour, je veux commander.',
                'agent_name' => 'Kova Market',
                'position' => 'right',
                'show_mobile' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2250102030405', Setting::get('contact.whatsapp'));
        $this->assertSame('Bonjour, je veux commander.', Setting::get('whatsapp_widget.message'));
        $this->assertSame('right', Setting::get('whatsapp_widget.position'));
        $this->assertSame('0', Setting::get('whatsapp_widget.show_mobile'));

        $this->get('/contact')->assertSee('wa-no-mobile', false);
    }

    public function test_the_number_must_be_in_international_format(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(WhatsAppButton::class)
            ->fillForm(['phone' => '+225 07 00 00 00 00'])
            ->call('save')
            ->assertHasFormErrors(['phone' => 'regex']);
    }

    public function test_a_picker_cannot_open_the_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get(WhatsAppButton::getUrl())->assertForbidden();
    }
}
