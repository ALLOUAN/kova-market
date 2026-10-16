<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\CourierChat;
use App\Models\Courier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Direct chat between a courier (courier area › Messages) and the team (Livraison › Messagerie livreurs).
 */
class CourierChatTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Courier $courier;

    private User $courierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->staff(Role::Manager)->create();
        [$this->courierUser, $this->courier] = $this->makeCourier('Moussa Traoré');
    }

    public function test_a_courier_writes_to_the_team_which_is_alerted_once_per_unread_burst(): void
    {
        $this->actingAs($this->courierUser);

        $this->postJson('/livreur/messages', ['body' => 'Le client ne répond pas.'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Le client ne répond pas.')
            ->assertJsonPath('message.mine', true);
        $this->postJson('/livreur/messages', ['body' => 'Je patiente 10 minutes.'])->assertCreated();

        $this->assertSame(2, $this->courier->messages()->where('from_staff', false)->count());
        $this->assertSame(1, $this->manager->notifications()->count());
        $this->assertSame('2', CourierChat::getNavigationBadge());
    }

    public function test_the_team_answers_and_the_courier_reads_it(): void
    {
        $this->courier->messages()->create(['user_id' => $this->courierUser->id, 'body' => 'Adresse introuvable.']);
        $this->actingAs($this->manager);

        Livewire::test(CourierChat::class, ['courierId' => $this->courier->id])
            ->assertSeeText('Adresse introuvable.')
            ->set('draft', 'Appelez le client au 07 00 00 00 00.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('draft', '');

        $this->assertNotNull($this->courier->messages()->where('from_staff', false)->first()->read_at);
        $answer = $this->courier->messages()->where('from_staff', true)->sole();
        $this->assertSame($this->manager->id, $answer->user_id);
        $this->assertNull($answer->read_at);

        $this->actingAs($this->courierUser)->get('/livreur')->assertSee('tabbar__badge', false);
        $this->get('/livreur/messages')->assertOk()->assertSeeText('Appelez le client au 07 00 00 00 00.');
        $this->assertNotNull($answer->refresh()->read_at);
    }

    public function test_the_poll_brings_only_new_messages_and_the_read_state(): void
    {
        $mine = $this->courier->messages()->create(['user_id' => $this->courierUser->id, 'body' => 'Bien reçu.', 'read_at' => now()]);
        $answer = $this->courier->messages()->create(['user_id' => $this->manager->id, 'from_staff' => true, 'body' => 'Merci !']);

        $this->actingAs($this->courierUser)->getJson("/livreur/messages/nouveaux?apres={$mine->id}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Merci !')
            ->assertJsonPath('messages.0.author', $this->manager->name)
            ->assertJsonPath('read_up_to', $mine->id);

        $this->assertNotNull($answer->refresh()->read_at);
    }

    public function test_a_courier_only_sees_their_own_conversation(): void
    {
        [, $other] = $this->makeCourier('Awa Koné');
        $other->messages()->create(['user_id' => $this->manager->id, 'from_staff' => true, 'body' => 'Message pour Awa']);

        $this->actingAs($this->courierUser)->get('/livreur/messages')->assertOk()->assertDontSeeText('Message pour Awa');
        $this->getJson('/livreur/messages/nouveaux?apres=0')->assertOk()->assertJsonCount(0, 'messages');
    }

    public function test_messages_are_escaped_and_must_not_be_empty(): void
    {
        $this->actingAs($this->courierUser);
        $this->post('/livreur/messages', ['body' => '  '])->assertSessionHasErrors('body');
        $this->post('/livreur/messages', ['body' => '<script>alert(1)</script>'])->assertRedirect(route('courier.messages'));

        $this->get('/livreur/messages')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_customers_and_pickers_cannot_reach_the_chat(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/livreur/messages')->assertForbidden();
        $this->assertDatabaseCount('courier_messages', 0);

        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get(CourierChat::getUrl())->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Courier}
     */
    private function makeCourier(string $name): array
    {
        $user = User::factory()->create(['name' => $name, 'phone' => '+22505'.fake()->unique()->numerify('########')]);
        $user->assignRole(Role::Courier->value);

        return [$user, $user->courier()->create(['transport' => 'moto'])];
    }
}
