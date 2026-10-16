<?php

namespace App\Http\Controllers\Account;

use App\Enums\ContactSubject;
use App\Enums\Permission;
use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketRequest;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Support\StaffRecipients;
use Filament\Actions\Action;
use Filament\Notifications\Notification as BackOfficeAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Assistance" in the customer area: the customer's requests to the team, each a conversation they can follow,
 * answer and close. The team answers from the back-office (Ventes › Assistance).
 */
class SupportTicketController extends Controller
{
    /** Shown when a new request is refused because another one still waits for the team. */
    private const ONE_AT_A_TIME = 'Votre demande précédente attend encore notre réponse. Vous pourrez en ouvrir une nouvelle dès que nous y aurons répondu ; en attendant, ajoutez ici tout complément.';

    public function index(Request $request): View
    {
        return view('pages.account.support.index', [
            'tickets' => $request->user()->supportTickets()->withCount('messages')->with('order')->paginate(10),
            'pending' => $request->user()->pendingSupportTicket(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($pending = $request->user()->pendingSupportTicket()) {
            return redirect()->route('account.support.show', $pending)->with('account_error', self::ONE_AT_A_TIME);
        }

        $orders = $request->user()->orders()->limit(20)->get(['id', 'number', 'created_at', 'total']);

        return view('pages.account.support.create', [
            'subjects' => array_values(array_filter(ContactSubject::cases(), fn (ContactSubject $subject) => $subject !== ContactSubject::Partnership)),
            'orders' => $orders,
            // "Un problème avec cette commande ?" from an order page.
            'selectedOrder' => $orders->firstWhere('number', $request->query('commande'))?->number,
        ]);
    }

    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $user = $request->user();

        // One request at a time: the next one once the team has answered.
        if ($pending = $user->pendingSupportTicket()) {
            return redirect()->route('account.support.show', $pending)->with('account_error', self::ONE_AT_A_TIME);
        }

        if ($user->supportTickets()->where('status', '!=', SupportTicketStatus::Closed)->count() >= SupportTicket::MAX_OPEN_PER_CUSTOMER) {
            return back()->withInput()->with('account_error', 'Vous avez déjà plusieurs demandes en cours : répondez ou fermez-en une avant d’en ouvrir une nouvelle.');
        }

        $ticket = DB::transaction(function () use ($request, $user): SupportTicket {
            $ticket = $user->supportTickets()->create([
                'order_id' => filled($request->validated('order')) ? Order::where('number', $request->validated('order'))->value('id') : null,
                'subject' => $request->validated('subject'),
                'title' => trim($request->validated('title')),
                'status' => SupportTicketStatus::Open,
            ]);
            $ticket->addMessage($user, $request->validated('message'), fromStaff: false,
                attachments: SupportTicketMessage::storeAttachments($ticket, $request->file('attachments', [])));

            return $ticket;
        });

        $this->alertStaff($ticket, "Nouvelle demande de {$user->name}");

        return redirect()->route('account.support.show', $ticket)->with('account_status', 'Votre demande est envoyée. Notre équipe vous répond ici, en général dans la journée.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorizeOwner($request, $ticket);

        if ($ticket->unread_by_customer) {
            $ticket->update(['unread_by_customer' => false]);
        }

        return view('pages.account.support.show', [
            'ticket' => $ticket->load(['messages' => fn ($query) => $query->oldest('id'), 'order']),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeOwner($request, $ticket);
        // A few words, or files alone ("voici la photo").
        $validated = $request->validate([
            'message' => ['required_without:attachments', 'nullable', 'string', 'min:2', 'max:3000'],
            ...SupportTicketRequest::attachmentRules(),
        ], SupportTicketRequest::attachmentMessages(), ['message' => 'message', 'attachments' => 'fichiers']);

        $ticket->addMessage($request->user(), (string) ($validated['message'] ?? ''), fromStaff: false,
            attachments: SupportTicketMessage::storeAttachments($ticket, $request->file('attachments', [])));
        $this->alertStaff($ticket, "Réponse de {$request->user()->name}");

        return redirect()->route('account.support.show', $ticket)->with('account_status', 'Votre message est envoyé.');
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeOwner($request, $ticket);
        $ticket->close();

        return redirect()->route('account.support.show', $ticket)->with('account_status', 'Demande fermée. Vous pouvez la rouvrir à tout moment en écrivant un nouveau message.');
    }

    /**
     * A file of the conversation, shown in the browser (pictures, PDF): for the customer of the request and the team.
     */
    public function attachment(Request $request, SupportTicket $ticket, SupportTicketMessage $message, int $index): StreamedResponse
    {
        abort_unless($ticket->user_id === $request->user()->getKey() || $request->user()->can(Permission::ManageOrders->value), 404);
        abort_unless($message->support_ticket_id === $ticket->getKey(), 404);

        $file = $message->attachments[$index] ?? abort(404);
        $disk = Storage::disk(SupportTicketMessage::DISK);
        abort_unless($disk->exists($file['path']), 404);

        return $disk->response($file['path'], $file['name'], ['Cache-Control' => 'private, max-age=3600'], 'inline');
    }

    private function authorizeOwner(Request $request, SupportTicket $ticket): void
    {
        // Another customer's request does not exist, as far as this customer knows.
        abort_unless($ticket->user_id === $request->user()->getKey(), 404);
    }

    /**
     * The bell of the back-office for whoever handles orders.
     */
    private function alertStaff(SupportTicket $ticket, string $title): void
    {
        BackOfficeAlert::make()
            ->title($title)
            ->body("{$ticket->reference()} · {$ticket->title} : ".Str::limit((string) $ticket->latestMessage()->value('body'), 80))
            ->icon('heroicon-o-lifebuoy')
            ->actions([Action::make('open')->label('Répondre')->url(SupportTicketResource::getUrl('view', ['record' => $ticket]))])
            ->sendToDatabase(StaffRecipients::with(Permission::ManageOrders));
    }
}
