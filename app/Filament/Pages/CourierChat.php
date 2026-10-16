<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Courier;
use App\Models\CourierMessage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Livraison › Messagerie livreurs: the team's chat with each courier, drawn like WhatsApp. The couriers on the left
 * (unread first, then the latest conversations), the chosen conversation on the right; refreshed every 5 seconds,
 * and the courier's messages count as read once shown here.
 */
class CourierChat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Livraison';

    protected static ?int $navigationSort = -5;

    protected static ?string $navigationLabel = 'Messagerie livreurs';

    protected static ?string $title = 'Messagerie livreurs';

    protected static ?string $slug = 'messagerie-livreurs';

    #[Url(as: 'livreur')]
    public ?int $courierId = null;

    public string $draft = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageDelivery->value);
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = CourierMessage::query()->unreadByStaff()->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Messages non lus';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function open(int $courierId): void
    {
        $this->courierId = $courierId;
        $this->draft = '';
        $this->resetErrorBag();
    }

    public function send(): void
    {
        $courier = $this->selected();
        abort_if($courier === null, 404);

        $this->validate(['draft' => ['required', 'string', 'max:'.CourierMessage::MAX_LENGTH]], [], ['draft' => 'message']);

        $courier->messages()->create([
            'user_id' => auth()->id(),
            'from_staff' => true,
            'body' => trim($this->draft),
        ]);

        $this->draft = '';
        $this->dispatch('courier-chat-sent');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pages.courier-chat')->viewData(fn () => $this->chat()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function chat(): array
    {
        $courier = $this->selected();

        // Shown here: the courier's messages are read.
        $courier?->messages()->unreadByStaff()->update(['read_at' => now()]);

        return [
            'couriers' => $this->couriers(),
            'courier' => $courier,
            'messages' => $courier
                ? $courier->messages()->with('author')->latest('id')->limit(200)->get()->reverse()
                    ->map(fn (CourierMessage $message) => $message->toBubble(viewerIsStaff: true))->values()
                : collect(),
        ];
    }

    private function selected(): ?Courier
    {
        return $this->courierId ? Courier::with('user')->find($this->courierId) : null;
    }

    /**
     * Every courier, with their unread count and last message: unread first, then the latest conversations.
     *
     * @return Collection<int, array{id: int, name: string, phone: string, suspended: bool, unread: int, last: ?string, last_at: ?string, sort: string}>
     */
    private function couriers(): Collection
    {
        $couriers = Courier::with('user')
            ->withCount(['messages as unread_count' => fn ($query) => $query->unreadByStaff()])
            ->addSelect(['last_message_id' => CourierMessage::select('id')->whereColumn('courier_id', 'couriers.id')->latest('id')->limit(1)])
            ->get()
            ->filter(fn (Courier $courier) => $courier->user !== null);
        $lastMessages = CourierMessage::findMany($couriers->pluck('last_message_id')->filter())->keyBy('id');

        return $couriers
            ->map(function (Courier $courier) use ($lastMessages) {
                $last = $lastMessages->get($courier->last_message_id);

                return [
                    'id' => $courier->id,
                    'name' => $courier->name(),
                    'phone' => $courier->formattedPhone(),
                    'suspended' => $courier->isSuspended(),
                    'unread' => (int) $courier->unread_count,
                    'last' => $last ? ($last->from_staff ? 'Vous : ' : '').$last->body : null,
                    'last_at' => $last?->created_at->isToday() ? $last->created_at->format('H:i') : $last?->created_at->format('d/m'),
                    'sort' => sprintf('%d-%010d', $courier->unread_count > 0 ? 1 : 0, $courier->last_message_id ?? 0),
                ];
            })
            ->sortByDesc('sort')
            ->values();
    }
}
