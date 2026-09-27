<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('topic')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('question')->label('Question')->searchable()->wrap(),
                TextColumn::make('position')->label('Ordre')->sortable(),
                ToggleColumn::make('is_published')->label('Publiée'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
