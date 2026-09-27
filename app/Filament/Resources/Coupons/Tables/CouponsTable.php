<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Support\Money;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('benefit')
                    ->label('Avantage')
                    ->state(fn (Coupon $record) => $record->benefitLabel())
                    ->description(fn (Coupon $record) => $record->minimum_subtotal ? 'Dès '.Money::format($record->minimum_subtotal) : null),
                TextColumn::make('target')
                    ->label('Articles')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('times_used')
                    ->label('Utilisations')
                    ->formatStateUsing(fn (Coupon $record) => $record->usage_limit ? "{$record->times_used} / {$record->usage_limit}" : (string) $record->times_used)
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Jusqu’au')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sans limite')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('État')
                    ->badge()
                    ->state(fn (Coupon $record) => match (true) {
                        ! $record->is_active => 'Désactivé',
                        $record->ends_at?->isPast() => 'Expiré',
                        $record->usage_limit !== null && $record->times_used >= $record->usage_limit => 'Épuisé',
                        (bool) $record->starts_at?->isFuture() => 'À venir',
                        default => 'En cours',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'En cours' => 'success',
                        'À venir' => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_public')->label('Affiché'),
                ToggleColumn::make('is_active')->label('Actif'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(CouponType::class),
                TernaryFilter::make('is_active')->label('Actif'),
                TernaryFilter::make('expired')
                    ->label('Expirés')
                    ->placeholder('Tous')
                    ->trueLabel('Seulement les expirés')
                    ->falseLabel('Non expirés')
                    ->queries(
                        true: fn (Builder $query) => $query->where('ends_at', '<', now()),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now())),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                // A used code stays for the order history: it can only be switched off.
                DeleteAction::make()->hidden(fn (Coupon $record) => $record->usages()->exists()),
            ]);
    }
}
