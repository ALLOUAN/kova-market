<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Message')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('subject')->label('Sujet')->badge()->color('gray'),
                        TextEntry::make('message')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (string $state) => nl2br(e($state)))
                            ->html(),
                    ]),
                Section::make('Client')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('name')->label('Nom')->weight('bold'),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->formatStateUsing(fn (ContactMessage $record) => $record->formattedPhone())
                            ->url(fn (ContactMessage $record) => 'tel:'.$record->phone),
                        TextEntry::make('email')->label('E-mail')->placeholder('—')->copyable(),
                        TextEntry::make('user.name')->label('Compte client')->placeholder('Sans compte'),
                        TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y à H:i'),
                        TextEntry::make('handled_at')
                            ->label('Traité')
                            ->formatStateUsing(fn (ContactMessage $record) => $record->handled_at->format('d/m/Y à H:i').($record->handler ? ' par '.$record->handler->name : ''))
                            ->placeholder('Pas encore'),
                    ]),
            ]);
    }
}
