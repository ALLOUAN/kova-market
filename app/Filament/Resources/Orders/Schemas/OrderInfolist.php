<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $state) => $state === null ? null : Money::format($state);

        return $schema
            ->columns(3)
            ->components([
                Section::make('Commande')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('number')->label('Numéro')->weight('bold')->copyable(),
                        TextEntry::make('created_at')->label('Passée le')->dateTime('d/m/Y à H:i'),
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('payment_method')->label('Mode de paiement'),
                        TextEntry::make('payment_status')->label('Paiement')->badge(),
                        TextEntry::make('source')->label('Canal')->formatStateUsing(fn (string $state) => $state === 'web' ? 'Site web' : 'Application mobile'),
                    ]),
                Section::make('Client et livraison')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer_name')->label('Client'),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->formatStateUsing(fn (Order $record) => $record->formattedPhone())
                            ->url(fn (Order $record) => 'tel:'.$record->phone),
                        TextEntry::make('email')->label('E-mail')->placeholder('—'),
                        TextEntry::make('user.name')->label('Compte client')->placeholder('Commande invité'),
                        TextEntry::make('commune_name')->label('Commune')->formatStateUsing(fn (Order $record) => "{$record->commune_name} ({$record->zone_name})"),
                        TextEntry::make('district')->label('Quartier'),
                        TextEntry::make('landmark')->label('Repère')->placeholder('—'),
                        TextEntry::make('note')->label('Note du client')->placeholder('—'),
                    ]),
                Section::make('Articles')
                    ->columnSpan(3)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                RepeatableEntry\TableColumn::make('Produit'),
                                RepeatableEntry\TableColumn::make('Référence'),
                                RepeatableEntry\TableColumn::make('Prix unitaire'),
                                RepeatableEntry\TableColumn::make('Quantité'),
                                RepeatableEntry\TableColumn::make('Total'),
                            ])
                            ->schema([
                                TextEntry::make('product_name')->belowContent(fn ($record) => $record->variant_label),
                                TextEntry::make('sku'),
                                TextEntry::make('unit_price')->formatStateUsing($money),
                                TextEntry::make('quantity'),
                                TextEntry::make('line_total')->formatStateUsing($money),
                            ]),
                        TextEntry::make('subtotal')->label('Sous-total')->formatStateUsing($money)->inlineLabel(),
                        TextEntry::make('coupon_code')->label('Code promo')->badge()->inlineLabel()->visible(fn ($record) => filled($record->coupon_code)),
                        TextEntry::make('discount')->label('Remise')->formatStateUsing(fn (int $state) => '−'.Money::format($state))->inlineLabel()->visible(fn ($record) => $record->discount > 0),
                        TextEntry::make('shipping_fee')->label('Livraison')->formatStateUsing(fn (int $state) => $state === 0 ? 'Offerte' : Money::format($state))->inlineLabel(),
                        TextEntry::make('total')->label('Total')->formatStateUsing($money)->weight('bold')->inlineLabel(),
                    ]),
                Section::make('Historique')
                    ->columnSpan(3)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('statusHistory')
                            ->hiddenLabel()
                            ->table([
                                RepeatableEntry\TableColumn::make('Date'),
                                RepeatableEntry\TableColumn::make('Statut'),
                                RepeatableEntry\TableColumn::make('Par'),
                                RepeatableEntry\TableColumn::make('Motif'),
                            ])
                            ->schema([
                                TextEntry::make('created_at')->dateTime('d/m/Y H:i'),
                                TextEntry::make('to_status')->badge(),
                                TextEntry::make('user.name')->placeholder('Client'),
                                TextEntry::make('note')->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
