<?php

namespace Tests\Feature;

use App\Enums\ContactSubject;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\SupportTicketStatus;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create(['name' => 'Aya Kouassi']);
    }

    public function test_guests_are_asked_to_sign_in(): void
    {
        $this->get('/compte/assistance')->assertRedirect(route('home', ['connexion' => 1]));
        $this->post('/compte/assistance', ['subject' => 'commande', 'title' => 'Retard', 'message' => 'Ma commande est en retard.'])->assertRedirect(route('home', ['connexion' => 1]));
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_a_customer_sends_a_request_about_their_order_and_the_team_is_alerted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->staff(Role::Manager)->create();
        $order = $this->order($this->customer);

        $this->actingAs($this->customer)->get("/compte/assistance/nouvelle?commande={$order->number}")
            ->assertOk()
            ->assertSee('<option value="'.$order->number.'" selected', false);

        $response = $this->post('/compte/assistance', [
            'subject' => 'commande',
            'order' => $order->number,
            'title' => 'Ma commande n’est pas arrivée',
            'message' => 'Bonjour, j’attends toujours ma commande.',
        ]);

        $ticket = SupportTicket::sole();
        $response->assertRedirect(route('account.support.show', $ticket));
        $this->assertSame($order->id, $ticket->order_id);
        $this->assertSame(SupportTicketStatus::Open, $ticket->status);
        $this->assertSame(ContactSubject::Order, $ticket->subject);
        $this->assertSame('Bonjour, j’attends toujours ma commande.', $ticket->messages()->sole()->body);
        $this->assertFalse($ticket->messages()->sole()->from_staff);
        $this->assertSame(1, $manager->notifications()->count());

        $this->get('/compte/assistance')->assertOk()->assertSeeText('Ma commande n’est pas arrivée')->assertSeeText('En attente de réponse');
    }

    public function test_the_request_needs_a_subject_a_title_and_a_message(): void
    {
        $this->actingAs($this->customer)->post('/compte/assistance', [])
            ->assertSessionHasErrors(['subject', 'title', 'message']);

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_another_customers_order_cannot_be_attached(): void
    {
        $theirs = $this->order(User::factory()->customer()->create());

        $this->actingAs($this->customer)->post('/compte/assistance', [
            'subject' => 'commande', 'order' => $theirs->number, 'title' => 'Ma commande', 'message' => 'Où est ma commande ?',
        ])->assertSessionHasErrors(['order' => 'Le champ commande sélectionné est invalide.']);

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_a_customer_with_too_many_open_requests_must_close_one_first(): void
    {
        SupportTicket::factory()->count(SupportTicket::MAX_OPEN_PER_CUSTOMER)->for($this->customer)->create(['status' => SupportTicketStatus::Answered]);

        $this->actingAs($this->customer)->post('/compte/assistance', [
            'subject' => 'autre', 'title' => 'Encore une question', 'message' => 'Une question de plus pour vous.',
        ])->assertSessionHas('account_error');

        $this->assertDatabaseCount('support_tickets', SupportTicket::MAX_OPEN_PER_CUSTOMER);
    }

    public function test_no_new_request_until_the_team_has_answered_the_previous_one(): void
    {
        $pending = SupportTicket::factory()->for($this->customer)->withMessage()->create(['title' => 'Colis en retard']);
        $this->actingAs($this->customer);

        $this->get('/compte/assistance')->assertOk()->assertSeeText('« Colis en retard » attend notre réponse.')->assertDontSeeText('Nouvelle demande');
        $this->get('/compte/assistance/nouvelle')->assertRedirect(route('account.support.show', $pending));
        $this->post('/compte/assistance', ['subject' => 'autre', 'title' => 'Autre question', 'message' => 'Une autre question pour vous.'])
            ->assertRedirect(route('account.support.show', $pending))
            ->assertSessionHas('account_error');
        $this->assertDatabaseCount('support_tickets', 1);

        // Completing the pending request stays possible.
        $this->post("/compte/assistance/{$pending->public_id}/messages", ['message' => 'Un complément.'])->assertSessionHasNoErrors();

        // Answered: the next request can be sent.
        $pending->addMessage(User::factory()->create(), 'Votre colis arrive demain.', fromStaff: true);
        $this->get('/compte/assistance/nouvelle')->assertOk();
        $this->post('/compte/assistance', ['subject' => 'autre', 'title' => 'Autre question', 'message' => 'Une autre question pour vous.']);
        $this->assertDatabaseCount('support_tickets', 2);
    }

    public function test_another_customers_request_does_not_exist_for_them(): void
    {
        $theirs = SupportTicket::factory()->withMessage()->create();

        $this->actingAs($this->customer);
        $this->get("/compte/assistance/{$theirs->public_id}")->assertNotFound();
        $this->post("/compte/assistance/{$theirs->public_id}/messages", ['message' => 'Bonjour'])->assertNotFound();
        $this->post("/compte/assistance/{$theirs->public_id}/fermer")->assertNotFound();

        $this->assertSame(1, $theirs->messages()->count());
        $this->assertSame(SupportTicketStatus::Open, $theirs->refresh()->status);
    }

    public function test_reading_the_teams_answer_marks_it_read_and_replying_hands_it_back_to_the_team(): void
    {
        $ticket = SupportTicket::factory()->for($this->customer)->withMessage()->create();
        $ticket->addMessage(User::factory()->create(), 'Votre colis part demain.', fromStaff: true);
        $this->assertTrue($ticket->refresh()->unread_by_customer);

        $this->actingAs($this->customer)->get('/compte')->assertSee('kova-account-nav__badge', false);
        $this->get("/compte/assistance/{$ticket->public_id}")->assertOk()->assertSeeText('Votre colis part demain.')->assertSeeText('Réponse reçue');
        $this->assertFalse($ticket->refresh()->unread_by_customer);

        $this->post("/compte/assistance/{$ticket->public_id}/messages", ['message' => 'Merci beaucoup !'])
            ->assertRedirect(route('account.support.show', $ticket));

        $this->assertSame(SupportTicketStatus::Open, $ticket->refresh()->status);
        $this->assertSame('Merci beaucoup !', $ticket->messages()->latest('id')->first()->body);
    }

    public function test_a_customer_closes_their_request_and_a_new_message_reopens_it(): void
    {
        $ticket = SupportTicket::factory()->for($this->customer)->withMessage()->create();

        $this->actingAs($this->customer)->post("/compte/assistance/{$ticket->public_id}/fermer");
        $this->assertSame(SupportTicketStatus::Closed, $ticket->refresh()->status);
        $this->assertNotNull($ticket->closed_at);

        $this->post("/compte/assistance/{$ticket->public_id}/messages", ['message' => 'Le problème revient.']);
        $this->assertSame(SupportTicketStatus::Open, $ticket->refresh()->status);
        $this->assertNull($ticket->closed_at);
    }

    public function test_a_customer_joins_a_photo_and_a_pdf_only_they_and_the_team_can_open(): void
    {
        Storage::fake(SupportTicketMessage::DISK);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->customer)->post('/compte/assistance', [
            'subject' => 'produit', 'title' => 'Article abîmé', 'message' => 'Le carton est arrivé écrasé.',
            'attachments' => [UploadedFile::fake()->image('colis.jpg'), UploadedFile::fake()->create('recu.pdf', 200, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $message = SupportTicketMessage::sole();
        $this->assertSame(['colis.jpg', 'recu.pdf'], array_column($message->attachments, 'name'));
        Storage::disk(SupportTicketMessage::DISK)->assertExists($message->attachments[0]['path']);
        $photo = route('account.support.attachment', [$message->ticket, $message, 0]);

        $this->get(route('account.support.show', $message->ticket))->assertOk()->assertSee('src="'.$photo.'"', false)->assertSeeText('recu.pdf');
        $this->get($photo)->assertOk()->assertHeader('content-type', 'image/jpeg');
        $this->get(route('account.support.attachment', [$message->ticket, $message, 5]))->assertNotFound();

        $this->actingAs(User::factory()->customer()->create())->get($photo)->assertNotFound();
        $this->actingAs(User::factory()->staff(Role::Manager)->create())->get($photo)->assertOk();
        $this->actingAs(User::factory()->staff(Role::Picker)->create())->get($photo)->assertNotFound();
    }

    public function test_only_photos_and_pdf_are_accepted_three_at_most(): void
    {
        Storage::fake(SupportTicketMessage::DISK);
        $ticket = SupportTicket::factory()->for($this->customer)->withMessage()->create();
        $this->actingAs($this->customer);

        $this->post("/compte/assistance/{$ticket->public_id}/messages", ['attachments' => [UploadedFile::fake()->create('page.html', 10, 'text/html')]])
            ->assertSessionHasErrors(['attachments.0' => 'Seuls les photos (JPG, PNG, WebP) et les PDF sont acceptés.']);
        $this->post("/compte/assistance/{$ticket->public_id}/messages", ['attachments' => array_fill(0, 4, UploadedFile::fake()->image('photo.png'))])
            ->assertSessionHasErrors(['attachments' => 'Vous pouvez joindre 3 fichiers au plus.']);

        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame([], Storage::disk(SupportTicketMessage::DISK)->allFiles());
    }

    public function test_a_reply_can_be_a_photo_alone(): void
    {
        Storage::fake(SupportTicketMessage::DISK);
        $ticket = SupportTicket::factory()->for($this->customer)->withMessage()->create();

        $this->actingAs($this->customer)->post("/compte/assistance/{$ticket->public_id}/messages", ['attachments' => [UploadedFile::fake()->image('preuve.png')]])
            ->assertSessionHasNoErrors();
        $this->post("/compte/assistance/{$ticket->public_id}/messages", [])->assertSessionHasErrors('message');

        $this->assertSame('preuve.png', $ticket->messages()->latest('id')->first()->attachments[0]['name']);
    }

    public function test_deleting_a_request_deletes_its_files(): void
    {
        Storage::fake(SupportTicketMessage::DISK);
        $ticket = SupportTicket::factory()->for($this->customer)->create();
        $ticket->addMessage($this->customer, 'Photo', fromStaff: false, attachments: SupportTicketMessage::storeAttachments($ticket, [UploadedFile::fake()->image('a.jpg')]));

        $ticket->delete();

        $this->assertSame([], Storage::disk(SupportTicketMessage::DISK)->allFiles());
    }

    public function test_messages_are_shown_escaped(): void
    {
        $ticket = SupportTicket::factory()->for($this->customer)->withMessage('<script>alert(1)</script>')->create();

        $this->actingAs($this->customer)->get("/compte/assistance/{$ticket->public_id}")
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function order(User $user): Order
    {
        return Order::create([
            'number' => 'KM-261016-'.fake()->unique()->numerify('####'),
            'user_id' => $user->id,
            'status' => OrderStatus::Received,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'customer_name' => $user->name,
            'phone' => $user->phone,
            'commune_name' => 'Cocody',
            'zone_name' => 'Zone 1',
            'district' => 'Riviera',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'total' => 10000,
            'terms_accepted_at' => now(),
        ]);
    }
}
