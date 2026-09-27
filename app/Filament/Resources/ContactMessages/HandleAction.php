<?php

namespace App\Filament\Resources\ContactMessages;

use App\Models\ContactMessage;
use Filament\Actions\Action;

/**
 * "Marquer comme traité" / "Rouvrir" on a contact message, from the list and from the message page.
 */
class HandleAction
{
    public static function make(): Action
    {
        return Action::make('handle')
            ->label(fn (ContactMessage $record) => $record->handled_at ? 'Rouvrir' : 'Marquer comme traité')
            ->icon(fn (ContactMessage $record) => $record->handled_at ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
            ->color(fn (ContactMessage $record) => $record->handled_at ? 'gray' : 'success')
            ->action(function (ContactMessage $record): void {
                $record->forceFill($record->handled_at
                    ? ['handled_at' => null, 'handled_by' => null]
                    : ['handled_at' => now(), 'handled_by' => auth()->id()])->save();
            });
    }
}
