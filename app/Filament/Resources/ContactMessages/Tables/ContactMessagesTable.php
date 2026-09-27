<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\ContactSubject;
use App\Filament\Resources\ContactMessages\HandleAction;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (ContactMessage $record) => $record->formattedPhone()),
                TextColumn::make('subject')->label('Sujet')->badge()->color('gray'),
                TextColumn::make('message')->label('Message')->limit(80)->wrap()->searchable(),
                TextColumn::make('handled_at')
                    ->label('État')
                    ->badge()
                    ->state(fn (ContactMessage $record) => $record->handled_at ? 'Traité' : 'À traiter')
                    ->color(fn (ContactMessage $record) => $record->handled_at ? 'success' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('pending')
                    ->label('État')
                    ->placeholder('Tous')
                    ->trueLabel('À traiter')
                    ->falseLabel('Traités')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('handled_at'),
                        false: fn (Builder $query) => $query->whereNotNull('handled_at'),
                    ),
                SelectFilter::make('subject')->label('Sujet')->options(ContactSubject::class),
            ])
            ->recordActions([
                ViewAction::make(),
                HandleAction::make(),
                DeleteAction::make(),
            ]);
    }
}
