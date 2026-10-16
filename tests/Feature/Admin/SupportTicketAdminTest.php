<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportReplyForCustomer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_team_answers_and_the_customer_is_told_by_e_mail(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['email' => 'aya@example.com']);
        $ticket = SupportTicket::factory()->for($customer)->withMessage()->create();
        $manager = User::factory()->staff(Role::Manager)->create();

        $this->actingAs($manager);
        Livewire::test(ViewSupportTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertSeeText('Bonjour, ma commande n’est pas arrivée.')
            ->callAction('reply', ['body' => 'Votre colis part demain matin.']);

        $ticket->refresh();
        $answer = $ticket->messages()->latest('id')->first();
        $this->assertTrue($answer->from_staff);
        $this->assertSame($manager->id, $answer->user_id);
        $this->assertSame(SupportTicketStatus::Answered, $ticket->status);
        $this->assertTrue($ticket->unread_by_customer);
        Notification::assertSentTo($customer, SupportReplyForCustomer::class, function (SupportReplyForCustomer $notification) use ($customer) {
            $mail = $notification->toMail($customer);

            return in_array('Votre colis part demain matin.', $mail->introLines, true)
                && $mail->actionUrl === route('account.support.show', $notification->ticket);
        });
    }

    public function test_answering_can_close_the_request_and_closing_can_be_undone(): void
    {
        Notification::fake();
        $ticket = SupportTicket::factory()->withMessage()->create();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ListSupportTickets::class)
            ->callAction(TestAction::make('reply')->table($ticket), ['body' => 'C’est réglé.', 'close' => true]);
        $this->assertSame(SupportTicketStatus::Closed, $ticket->refresh()->status);

        Livewire::test(ViewSupportTicket::class, ['record' => $ticket->getRouteKey()])->callAction('toggleClosed');
        $this->assertSame(SupportTicketStatus::Answered, $ticket->refresh()->status);
        $this->assertNull($ticket->closed_at);
    }

    public function test_the_team_can_join_a_file_to_its_answer(): void
    {
        Notification::fake();
        Storage::fake(SupportTicketMessage::DISK);
        $ticket = SupportTicket::factory()->withMessage()->create();
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ViewSupportTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('reply', ['body' => 'Voici votre facture.', 'attachments' => [UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf')]])
            ->assertHasNoFormErrors();

        $file = $ticket->messages()->latest('id')->first()->attachments[0];
        $this->assertSame('facture.pdf', $file['name']);
        $this->assertStringStartsWith("support/{$ticket->public_id}/", $file['path']);
        Storage::disk(SupportTicketMessage::DISK)->assertExists($file['path']);
    }

    public function test_the_tabs_sort_requests_by_whose_turn_it_is(): void
    {
        $open = SupportTicket::factory()->create();
        $answered = SupportTicket::factory()->create(['status' => SupportTicketStatus::Answered]);
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(ListSupportTickets::class, ['activeTab' => 'open'])->assertCanSeeTableRecords([$open])->assertCanNotSeeTableRecords([$answered]);
        Livewire::test(ListSupportTickets::class, ['activeTab' => 'answered'])->assertCanSeeTableRecords([$answered])->assertCanNotSeeTableRecords([$open]);
        $this->assertSame('1', SupportTicketResource::getNavigationBadge());
    }

    public function test_a_picker_cannot_reach_the_requests(): void
    {
        $this->actingAs(User::factory()->staff(Role::Picker)->create())
            ->get(SupportTicketResource::getUrl('index'))
            ->assertForbidden();
    }
}
