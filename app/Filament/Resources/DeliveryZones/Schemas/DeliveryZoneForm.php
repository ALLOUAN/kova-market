<?php

namespace App\Filament\Resources\DeliveryZones\Schemas;

use App\Enums\DeliveryMode;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Models\Setting;
use App\Support\Money;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Zone')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nom')->required()->maxLength(100),
                        TextInput::make('delay_label')->label('Délai indicatif')->placeholder('J+1')->maxLength(50),
                        TextInput::make('fee')
                            ->label('Frais de livraison')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->helperText('0 pour une livraison gratuite dans cette zone.')
                            ->requiredIf('is_active', true),
                        TextInput::make('position')->label('Ordre')->integer()->minValue(0)->default(0)->required(),
                        Select::make('delivery_mode')
                            ->label('Mode de livraison')
                            ->options(DeliveryMode::class)
                            ->default(DeliveryMode::Abidjan)
                            ->required()
                            ->helperText('Abidjan : remise par un livreur KOVA (pas d’étape « Expédiée »). Intérieur : expédiée par transporteur, le client indique sa ville.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Disponibilité')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Livraison ouverte')
                            ->helperText('Fermée : les communes de la zone ne sont plus proposées au panier.'),
                    ]),
                // Shown to the customer in the cart and at checkout; the minimum and the threshold are applied there.
                Section::make('Conditions particulières')
                    ->description('Toutes facultatives. Le client les voit au panier et à la commande.')
                    ->columnSpan(3)
                    ->columns(2)
                    ->schema([
                        TextInput::make('min_order')
                            ->label('Commande minimum')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->helperText('Montant d’articles en dessous duquel la commande est refusée pour cette zone. Vide : aucun minimum.'),
                        TextInput::make('free_shipping_threshold')
                            ->label('Livraison offerte dès')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->helperText(fn () => 'Vide : le seuil général de la boutique s’applique ('.(filled($general = Setting::get('delivery.free_shipping_threshold')) ? Money::format((int) $general) : 'aucun').', Paramètres › Livraison).'),
                        CheckboxList::make('delivery_days')
                            ->label('Jours de livraison')
                            ->options(array_map('ucfirst', DeliveryZone::DAYS))
                            ->columns(['default' => 2, 'sm' => 4, 'lg' => 7])
                            ->helperText('Aucun jour coché : livraison tous les jours.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Communes et villes desservies')
                    ->columnSpan(3)
                    ->schema([
                        Repeater::make('communes')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->simple(
                                TextInput::make('name')
                                    ->label('Commune')
                                    ->required()
                                    ->maxLength(100)
                                    ->distinct()
                                    // A commune belongs to one zone only.
                                    ->rule(fn (?Commune $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                        $taken = Commune::query()->where('name', $value)
                                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                            ->with('zone')->first();

                                        if ($taken) {
                                            $fail("« {$value} » est déjà rattachée à la zone {$taken->zone?->name}.");
                                        }
                                    }),
                            )
                            ->addActionLabel('Ajouter une commune')
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
