<?php

namespace App\Http\Controllers\Courier;

use App\Enums\Permission;
use App\Filament\Pages\CourierChat;
use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierMessage;
use App\Support\StaffRecipients;
use Filament\Actions\Action;
use Filament\Notifications\Notification as BackOfficeAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Messages" in the courier area: the courier's chat with the back-office. The page sends without reloading and
 * asks every few seconds for what is new (poll), which also marks the team's messages as read.
 */
class MessageController extends Controller
{
    /** Messages shown when the page opens; older ones are rarely needed on the road. */
    private const SHOWN = 100;

    public function index(Request $request): View
    {
        $courier = $this->courier($request);
        $courier->messages()->unreadByCourier()->update(['read_at' => now()]);

        return view('courier.messages', [
            'courier' => $courier,
            'messages' => $courier->messages()->with('author')->latest('id')->limit(self::SHOWN)->get()->reverse()
                ->map(fn (CourierMessage $message) => $message->toBubble(viewerIsStaff: false))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $courier = $this->courier($request);
        $validated = $request->validate(['body' => ['required', 'string', 'max:'.CourierMessage::MAX_LENGTH]], [], ['body' => 'message']);

        // The team hears of the first message it has not read yet, not of every line of a burst.
        $alertTeam = $courier->messages()->unreadByStaff()->doesntExist();

        $message = $courier->messages()->create([
            'user_id' => $request->user()->getKey(),
            'from_staff' => false,
            'body' => trim($validated['body']),
        ]);

        if ($alertTeam) {
            BackOfficeAlert::make()
                ->title("Message de {$courier->name()}")
                ->body(Str::limit($message->body, 80))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->actions([Action::make('open')->label('Répondre')->url(CourierChat::getUrl(['livreur' => $courier->getKey()]))])
                ->sendToDatabase(StaffRecipients::with(Permission::ManageDelivery));
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message->toBubble(viewerIsStaff: false)], 201)
            : redirect()->route('courier.messages');
    }

    /**
     * What is new since the last message the page shows, and which of the courier's messages the team has read.
     */
    public function poll(Request $request): JsonResponse
    {
        $courier = $this->courier($request);
        $after = (int) $request->query('apres', 0);

        $new = $courier->messages()->with('author')->where('id', '>', $after)->orderBy('id')->get();
        $courier->messages()->unreadByCourier()->whereIn('id', $new->modelKeys())->update(['read_at' => now()]);

        return response()->json([
            'messages' => $new->map(fn (CourierMessage $message) => $message->toBubble(viewerIsStaff: false))->values(),
            'read_up_to' => (int) $courier->messages()->where('from_staff', false)->whereNotNull('read_at')->max('id'),
        ]);
    }

    private function courier(Request $request): Courier
    {
        return $request->user()->courier;
    }
}
