<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Editable search-engine title and description (F-151). Empty fields fall back to generated values.
 */
class SeoFields
{
    public static function section(): Section
    {
        return Section::make('Référencement (Google)')
            ->description('Facultatif : sans valeur, le nom et le début de la description sont utilisés.')
            ->collapsed()
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextInput::make('meta_title')->label('Titre dans Google')->maxLength(70),
                TextInput::make('meta_description')->label('Description dans Google')->maxLength(160),
            ]);
    }
}
