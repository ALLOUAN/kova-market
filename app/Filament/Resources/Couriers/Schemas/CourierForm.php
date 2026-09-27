<?php

namespace App\Filament\Resources\Couriers\Schemas;

use App\Enums\CourierTransport;
use App\Filament\Support\StorefrontImage;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\User;
use App\Rules\IvorianPhoneNumber;
use App\Support\PhoneNumber;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Livreur')
                    ->columnSpan(2)
                    ->columns(2)
                    ->description('Le téléphone sert d’identifiant. À la création, un mot de passe provisoire est envoyé par SMS.')
                    ->schema([
                        TextInput::make('name')->label('Nom complet')->required()->maxLength(255),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->placeholder('07 01 02 03 04')
                            ->required()
                            ->rules([
                                new IvorianPhoneNumber,
                                fn (?Courier $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    $taken = User::where('phone', PhoneNumber::normalize((string) $value))
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->user_id))
                                        ->exists();

                                    if ($taken) {
                                        $fail('Ce numéro est déjà utilisé par un autre compte.');
                                    }
                                },
                            ]),
                        Select::make('transport')
                            ->label('Moyen de transport')
                            ->options(CourierTransport::class)
                            ->default(CourierTransport::Motorbike)
                            ->required(),
                        Select::make('zones')
                            ->label('Zones desservies')
                            ->helperText('Le livreur voit et reçoit les commandes des communes de ces zones.')
                            ->options(fn () => DeliveryZone::orderBy('position')->orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->required(),
                    ]),
                Section::make('Photo')
                    ->columnSpan(1)
                    ->schema([
                        StorefrontImage::make('photo', 'couriers')
                            ->label('Photo')
                            ->helperText('Montrée au client qui attend sa livraison. JPG, PNG ou WebP, 2 Mo maximum.'),
                    ]),
            ]);
    }
}
