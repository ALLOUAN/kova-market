<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Notifications\SupportReplyForCustomer;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * "Répondre" / "Fermer" / "Rouvrir" on an assistance request, from the list and from the request page.
 */
class ReplyAction
{
    public static function make(): Action
    {
        return Action::make('reply')
            ->label('Répondre')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('primary')
            ->modalHeading(fn (SupportTicket $record) => "Répondre à {$record->user?->name}")
            ->modalDescription(fn (SupportTicket $record) => "{$record->reference()} · {$record->title}")
            ->modalSubmitActionLabel('Envoyer la réponse')
            ->schema([
                Textarea::make('body')
                    ->label('Votre réponse')
                    ->helperText('Visible dans l’espace client du client, et envoyée par e-mail si son compte en a un.')
                    ->rows(7)
                    ->required()
                    ->minLength(2)
                    ->maxLength(3000),
                FileUpload::make('attachments')
                    ->label('Fichiers')
                    ->helperText('Photos ou PDF, '.SupportTicketMessage::MAX_ATTACHMENTS.' fichiers au plus.')
                    ->disk(SupportTicketMessage::DISK)
                    ->directory(fn (SupportTicket $record) => "support/{$record->public_id}")
                    ->visibility('private')
                    ->multiple()
                    ->maxFiles(SupportTicketMessage::MAX_ATTACHMENTS)
                    ->maxSize(SupportTicketMessage::ATTACHMENT_MAX_KB)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->storeFileNamesIn('attachment_names'),
                Toggle::make('close')->label('Fermer la demande après cette réponse'),
            ])
            ->action(function (SupportTicket $record, array $data): void {
                $disk = Storage::disk(SupportTicketMessage::DISK);
                $attachments = array_map(fn (string $path) => [
                    'path' => $path,
                    'name' => $data['attachment_names'][$path] ?? basename($path),
                    'size' => $disk->size($path),
                ], array_values($data['attachments'] ?? []));

                $reply = $record->addMessage(auth()->user(), $data['body'], fromStaff: true, attachments: $attachments);

                if ($data['close'] ?? false) {
                    $record->close();
                }

                $record->user?->notify(new SupportReplyForCustomer($record, $reply));

                Notification::make()->title('Réponse envoyée')->success()->send();
            });
    }

    public static function toggleClosed(): Action
    {
        return Action::make('toggleClosed')
            ->label(fn (SupportTicket $record) => $record->isClosed() ? 'Rouvrir' : 'Fermer')
            ->icon(fn (SupportTicket $record) => $record->isClosed() ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check-circle')
            ->color(fn (SupportTicket $record) => $record->isClosed() ? 'gray' : 'success')
            ->action(function (SupportTicket $record): void {
                $record->isClosed()
                    ? $record->update([
                        // Back to whoever's turn it was: the customer's after the team's last word, the team's otherwise.
                        'status' => $record->latestMessage()->value('from_staff') ? SupportTicketStatus::Answered : SupportTicketStatus::Open,
                        'closed_at' => null,
                    ])
                    : $record->close();
            });
    }
}
