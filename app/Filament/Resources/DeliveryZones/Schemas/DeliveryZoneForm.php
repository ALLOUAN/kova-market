<?php

namespace App\Filament\Resources\DeliveryZones\Schemas;

use App\Models\Commune;
use Closure;
use Filament\Forms\Components\Repeater;
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
                    ]),
                Section::make('Disponibilité')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Livraison ouverte')
                            ->helperText('Fermée : les communes de la zone ne sont plus proposées au panier.'),
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
