<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Enums\ContactSubject;
use App\Filament\Resources\SupportTickets\ReplyAction;
use App\Models\SupportTicket;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'order'])->withCount('messages'))
            ->columns([
                TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Demande')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (SupportTicket $record) => $record->reference().' · '.$record->messages_count.' message'.($record->messages_count > 1 ? 's' : ''))
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (SupportTicket $record) => $record->user?->email ?: $record->user?->phone),
                TextColumn::make('subject')->label('Sujet')->badge()->color('gray'),
                TextColumn::make('order.number')->label('Commande')->placeholder('—'),
                TextColumn::make('status')->label('État')->badge(),
            ])
            ->filters([
                SelectFilter::make('subject')->label('Sujet')->options(ContactSubject::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Ouvrir'),
                ReplyAction::make(),
            ]);
    }
}
