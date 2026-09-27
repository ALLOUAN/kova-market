<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Name + slug pair: the slug follows the name while it has not been edited by hand.
 */
class SlugInput
{
    /**
     * @return array{0: TextInput, 1: TextInput}
     */
    public static function make(string $nameLabel = 'Nom'): array
    {
        return [
            TextInput::make('name')
                ->label($nameLabel)
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                    if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')
                ->label('Adresse (slug)')
                ->helperText('Partie de l’URL, par exemple « smartphones ».')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(ignoreRecord: true),
        ];
    }
}
