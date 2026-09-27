<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Coordonnées')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nom')->weight('bold'),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->state(fn (Customer $record) => $record->isGuest() ? 'Invité (sans compte)' : 'Compte client')
                            ->color(fn (Customer $record) => $record->isGuest() ? 'gray' : 'success'),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->formatStateUsing(fn (Customer $record) => $record->formattedPhone())
                            ->url(fn (Customer $record) => $record->phone ? 'tel:'.$record->phone : null)
                            ->placeholder('—'),
                        TextEntry::make('email')->label('E-mail')->placeholder('—')->copyable(),
                        TextEntry::make('created_at')
                            ->label(fn (Customer $record) => $record->isGuest() ? 'Première commande' : 'Compte créé le')
                            ->date('d/m/Y'),
                        TextEntry::make('user.marketing_opt_in')
                            ->label('Offres et nouveautés')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Accepte' : 'Refuse')
                            ->visible(fn (Customer $record) => ! $record->isGuest()),
                    ]),
                Section::make('Achats')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('orders_count')->label('Commandes')->inlineLabel(),
                        TextEntry::make('total_spent')
                            ->label('Total dépensé')
                            ->helperText('Commandes payées.')
                            ->formatStateUsing(fn (int $state) => Money::format($state))
                            ->weight('bold')
                            ->inlineLabel(),
                        TextEntry::make('last_order_at')->label('Dernière commande')->dateTime('d/m/Y')->placeholder('Aucune')->inlineLabel(),
                    ]),
                Section::make('Adresses')
                    ->columnSpan(3)
                    ->visible(fn (Customer $record) => ! $record->isGuest())
                    ->schema([
                        RepeatableEntry::make('addresses')
                            ->hiddenLabel()
                            ->state(fn (Customer $record) => $record->user?->addresses()->with('commune')->get() ?? collect())
                            ->table([
                                RepeatableEntry\TableColumn::make('Nom'),
                                RepeatableEntry\TableColumn::make('Adresse'),
                                RepeatableEntry\TableColumn::make('Téléphone'),
                            ])
                            ->schema([
                                TextEntry::make('label')->belowContent(fn (Address $record) => $record->is_default ? 'Par défaut' : null),
                                TextEntry::make('summary')->state(fn (Address $record) => $record->summary()),
                                TextEntry::make('phone')->placeholder('—'),
                            ])
                            ->placeholder('Aucune adresse enregistrée'),
                    ]),
                Section::make('Commandes')
                    ->columnSpan(3)
                    ->description('Les 50 dernières, compte et commandes passées avec ce numéro.')
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->hiddenLabel()
                            ->state(fn (Customer $record) => $record->orders()->limit(50)->get())
                            ->table([
                                RepeatableEntry\TableColumn::make('Numéro'),
                                RepeatableEntry\TableColumn::make('Date'),
                                RepeatableEntry\TableColumn::make('Statut'),
                                RepeatableEntry\TableColumn::make('Paiement'),
                                RepeatableEntry\TableColumn::make('Total'),
                            ])
                            ->schema([
                                TextEntry::make('number')->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))->weight('bold'),
                                TextEntry::make('created_at')->dateTime('d/m/Y H:i'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('payment_status')->badge(),
                                TextEntry::make('total')->formatStateUsing(fn (int $state) => Money::format($state)),
                            ])
                            ->placeholder('Aucune commande'),
                    ]),
            ]);
    }
}
