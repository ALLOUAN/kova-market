<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_shows_the_store_details_and_the_form(): void
    {
        Setting::store(['contact.opening_hours' => 'Lun - Sam : 08h00 - 19h00', 'contact.email' => 'contact@kovamarket.ci']);

        $this->get('/')->assertSee('href="'.route('contact.show').'"', false);

        $this->get('/contact')->assertOk()
            ->assertSeeText('Lun - Sam : 08h00 - 19h00')
            ->assertSeeText('contact@kovamarket.ci')
            ->assertSee('name="started_at"', false)
            ->assertSee('name="website"', false)
            ->assertDontSee('cf-turnstile', false);
    }

    public function test_a_real_message_reaches_the_back_office_and_the_contact_address(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->staff(Role::Manager)->create();
        Setting::store(['contact.email' => 'contact@kovamarket.ci']);

        $this->send()->assertRedirect('/contact')->assertSessionHas('notice');

        $message = ContactMessage::sole();
        $this->assertSame(['Awa Koné', '+2250701020304', 'commande'], [$message->name, $message->phone, $message->subject->value]);
        Notification::assertSentOnDemand(ContactMessageReceived::class, fn ($notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'contact@kovamarket.ci');
        Notification::assertSentTo($manager, FilamentDatabaseNotification::class, fn ($notification) => $notification->data['title'] === 'Message de Awa Koné');
    }

    public function test_robots_are_blocked(): void
    {
        $blocked = 'Votre message n’a pas pu être envoyé. Merci de réessayer dans un instant.';

        $this->send(['website' => 'http://spam.example'])->assertSessionHasErrors(['message' => $blocked]);
        $this->send(['started_at' => Crypt::encryptString((string) now()->timestamp)])->assertSessionHasErrors(['message' => $blocked]);
        $this->send(['started_at' => 'forged'])->assertSessionHasErrors(['message' => $blocked]);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_turnstile_is_checked_once_configured(): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret']);
        $this->get('/contact')->assertSee('data-sitekey="site-key"', false);

        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->send(['cf-turnstile-response' => 'bad'])->assertSessionHasErrors('message');
        $this->send(['cf-turnstile-response' => 'good'])->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
        Http::assertSent(fn ($request) => $request['secret'] === 'secret' && $request['response'] === 'good');
    }

    public function test_managers_handle_messages_and_pickers_cannot_read_them(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->send();
        $message = ContactMessage::sole();

        $this->actingAs($manager = User::factory()->staff(Role::Manager)->create());
        $this->get(ContactMessageResource::getUrl('view', ['record' => $message]))->assertOk()->assertSeeText('Où en est ma commande KM-261001-0001 ?');

        Livewire::test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$message])
            ->callAction(TestAction::make('handle')->table($message))
            ->assertCanNotSeeTableRecords([$message]);

        $this->assertTrue($message->fresh()->handler->is($manager));
        $this->assertNull(ContactMessageResource::getNavigationBadge());

        $this->actingAs(User::factory()->staff(Role::Picker)->create());
        $this->get(ContactMessageResource::getUrl('index'))->assertForbidden();
    }

    public function test_the_whatsapp_button_prefills_the_product_viewed(): void
    {
        Product::factory()->create(['name' => 'Casque Bose', 'slug' => 'casque-bose', 'price' => 45000]);

        $this->get('/')->assertDontSee('kova-whatsapp-button', false);

        Setting::store(['contact.whatsapp' => '2250700000000']);

        $this->get('/')->assertSee('https://wa.me/2250700000000?text='.rawurlencode('Bonjour KOVA MARKET, j’ai une question.'), false);
        $this->get('/produit/casque-bose')->assertSee(
            'https://wa.me/2250700000000?text='.rawurlencode("Bonjour, je suis intéressé(e) par « Casque Bose » (45\u{00A0}000\u{00A0}FCFA) : ".route('products.show', 'casque-bose')),
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function send(array $overrides = []): TestResponse
    {
        return $this->post('/contact', [
            'name' => 'Awa Koné',
            'phone' => '07 01 02 03 04',
            'subject' => 'commande',
            'message' => 'Où en est ma commande KM-261001-0001 ?',
            'started_at' => Crypt::encryptString((string) now()->subSeconds(20)->timestamp),
            'website' => '',
            ...$overrides,
        ]);
    }
}
