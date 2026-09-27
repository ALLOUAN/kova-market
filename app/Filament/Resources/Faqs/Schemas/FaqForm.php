<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Models\Faq;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question')
                    ->columns(2)
                    ->schema([
                        TextInput::make('topic')
                            ->label('Rubrique')
                            ->placeholder('Paiement, Livraison…')
                            ->datalist(fn () => Faq::query()->distinct()->orderBy('topic')->pluck('topic')->all())
                            ->required()
                            ->maxLength(100),
                        TextInput::make('position')->label('Ordre dans la rubrique')->integer()->minValue(0)->default(0)->required(),
                        TextInput::make('question')->label('Question')->required()->maxLength(255)->columnSpanFull(),
                        Textarea::make('answer')->label('Réponse')->required()->rows(5)->columnSpanFull(),
                        Toggle::make('is_published')->label('Publiée')->default(true),
                    ]),
            ]);
    }
}
