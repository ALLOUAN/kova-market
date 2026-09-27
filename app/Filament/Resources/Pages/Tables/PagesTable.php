<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('slug')->label('Adresse')->prefix('/page/')->color('gray'),
                ToggleColumn::make('is_published')->label('Publiée'),
                TextColumn::make('updated_at')->label('Modifiée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $record) => route('pages.show', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
