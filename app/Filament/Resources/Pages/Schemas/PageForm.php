<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Page')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')->label('Titre')->required()->maxLength(255),
                        RichEditor::make('content')
                            ->label('Contenu')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['undo', 'redo'],
                            ]),
                    ]),
                Section::make('Publication et référencement')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_published')->label('Publiée')->default(true),
                        TextInput::make('slug')
                            ->label('Adresse (slug)')
                            ->helperText('Les liens du menu et du pied de page utilisent cette adresse : ne la changez pas sans les mettre à jour.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('meta_title')->label('Titre pour Google')->maxLength(70),
                        TextInput::make('meta_description')->label('Description pour Google')->maxLength(160),
                    ]),
            ]);
    }
}
