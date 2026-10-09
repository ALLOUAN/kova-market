<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Finance\FinancePeriod;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Commande')->searchable()->weight('bold'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer_name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Order $record) => $record->formattedPhone()),
                TextColumn::make('phone')->label('Téléphone')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commune_name')->label('Destination')
                    ->formatStateUsing(fn (Order $record) => $record->destinationLabel())
                    ->description(fn (Order $record) => $record->zone_name),
                // Products (after discounts) and delivery fees, then the total; the footer sums the orders shown (filters).
                TextColumn::make('products')
                    ->label('Produits')
                    ->state(fn (Order $record) => $record->total - $record->shipping_fee)
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->summarize(Summarizer::make()->label('Produits')->using(fn (QueryBuilder $query) => (int) $query->sum(DB::raw('total - shipping_fee')))->formatStateUsing(fn ($state) => Money::format((int) $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shipping_fee')
                    ->label('Livraison')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->summarize(Sum::make()->label('Frais')->formatStateUsing(fn ($state) => Money::format((int) $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Money::format((int) $state)))
                    ->sortable(),
                TextColumn::make('payment_method')->label('Mode de paiement')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_status')->label('Paiement')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('courier.user.name')->label('Livreur')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('courier_id')
                    ->label('Livreur')
                    ->relationship('courier.user', 'name'),
                Filter::make('unassigned')
                    ->label('Sans livreur (à livrer)')
                    ->query(fn (Builder $query) => $query->whereNull('courier_id')->whereIn('status', Courier::OPEN_STATUSES)),
                SelectFilter::make('status')->label('Statut')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('payment_status')->label('Paiement')->options(PaymentStatus::class),
                SelectFilter::make('payment_method')->label('Mode de paiement')->options(PaymentMethod::class),
                SelectFilter::make('delivery_mode')->label('Mode de livraison')->options(DeliveryMode::class),
                SelectFilter::make('zone_name')
                    ->label('Zone')
                    ->options(fn () => Order::query()->distinct()->orderBy('zone_name')->pluck('zone_name', 'zone_name')->all()),
                // Order date: a ready-made period (today, this week...) or chosen dates.
                Filter::make('period')
                    ->schema([
                        Select::make('preset')->label('Période')->placeholder('Dates choisies')->options(array_diff_key(FinancePeriod::PRESETS, ['custom' => true])),
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['preset'] ?? null)) {
                            $period = FinancePeriod::make($data['preset']);

                            return $query->whereBetween('created_at', [$period->from, $period->to]);
                        }

                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (filled($data['preset'] ?? null)) {
                            return FinancePeriod::PRESETS[$data['preset']] ?? null;
                        }

                        return match (true) {
                            filled($data['from'] ?? null) && filled($data['until'] ?? null) => 'Du '.Carbon::parse($data['from'])->format('d/m/Y').' au '.Carbon::parse($data['until'])->format('d/m/Y'),
                            filled($data['from'] ?? null) => 'Depuis le '.Carbon::parse($data['from'])->format('d/m/Y'),
                            filled($data['until'] ?? null) => 'Jusqu’au '.Carbon::parse($data['until'])->format('d/m/Y'),
                            default => null,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Ouvrir'),
            ])
            ->emptyStateHeading('Aucune commande');
    }
}
