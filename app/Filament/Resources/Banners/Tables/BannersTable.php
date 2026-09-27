<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->defaultGroup('placement')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront'),
                TextColumn::make('title')
                    ->label('Texte')
                    ->state(fn (Banner $record) => trim("{$record->highlight} {$record->title}"))
                    ->description(fn (Banner $record) => $record->subtitle),
                TextColumn::make('placement')
                    ->label('Emplacement')
                    ->formatStateUsing(fn (BannerPlacement $state) => $state->label()),
                TextColumn::make('position')
                    ->label('Ordre')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Jusqu’au')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sans limite'),
                ToggleColumn::make('is_visible')
                    ->label('Visible'),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->label('Emplacement')
                    ->options(BannerPlacement::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Aucune bannière')
            ->emptyStateDescription('Tant qu’aucune bannière n’est visible pour un emplacement, l’accueil affiche le contenu par défaut.');
    }
}
