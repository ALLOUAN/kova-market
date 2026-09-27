<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('commune_name')->label('Commune')->description(fn (Order $record) => $record->zone_name),
                TextColumn::make('total')->label('Total')->formatStateUsing(fn (int $state) => Money::format($state))->sortable(),
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
                SelectFilter::make('zone_name')
                    ->label('Zone')
                    ->options(fn () => Order::query()->distinct()->orderBy('zone_name')->pluck('zone_name', 'zone_name')->all()),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()->label('Ouvrir'),
            ])
            ->emptyStateHeading('Aucune commande');
    }
}
