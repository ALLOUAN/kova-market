<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\BadgeVariant;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Catégorie')
                    ->columns(2)
                    ->schema([
                        ...SlugInput::make(),
                        Select::make('parent_id')
                            ->label('Catégorie parente')
                            ->helperText('Vide pour un rayon principal. Trois niveaux maximum.')
                            // A category can only hang under a root or a second-level category, never under itself.
                            ->relationship(
                                'parent',
                                'name',
                                fn (Builder $query, ?Category $record) => $query
                                    ->where(fn (Builder $query) => $query->whereNull('parent_id')
                                        ->orWhereHas('parent', fn (Builder $query) => $query->whereNull('parent_id')))
                                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())),
                            )
                            ->searchable()
                            ->preload(),
                        TextInput::make('position')
                            ->label('Ordre d’affichage')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        TextInput::make('tagline')
                            ->label('Accroche')
                            ->maxLength(255),
                        TextInput::make('icon')
                            ->label('Icône (Font Awesome)')
                            ->placeholder('fa-regular fa-camera')
                            ->maxLength(255),
                        Toggle::make('is_featured')
                            ->label('Mise en avant sur l’accueil'),
                    ]),
                Section::make('Visuel et badge')
                    ->columns(2)
                    ->schema([
                        StorefrontImage::make('image', 'categories', [176, 176], 'carrée, fond transparent')
                            ->label('Image')
                            ->columnSpanFull(),
                        TextInput::make('badge_label')
                            ->label('Badge')
                            ->placeholder('NOUVEAU')
                            ->maxLength(30),
                        Select::make('badge_variant')
                            ->label('Couleur du badge')
                            ->options(BadgeVariant::options())
                            ->requiredWith('badge_label'),
                    ]),
                SeoFields::section(),
                Section::make('Encart promotionnel du menu')
                    ->description('Affiché dans le méga-menu « Boutique » (catégories principales) et le panneau des catégories.')
                    ->columns(2)
                    ->collapsed()
                    ->statePath('promo')
                    ->schema([
                        StorefrontImage::make('menu_image', 'categories', [630, 1008], 'fond de l’encart du méga-menu « Boutique » ; vide : l’image du thème')
                            ->label('Image de fond du méga-menu'),
                        StorefrontImage::make('image', 'categories', [593, 240], 'panneau des catégories')
                            ->label('Image du panneau des catégories'),
                        TextInput::make('label')->label('Libellé')->placeholder('À partir de'),
                        TextInput::make('highlight')->label('Mise en avant')->placeholder('11 décembre'),
                        TextInput::make('title')->label('Titre')->placeholder('Jusqu’à -40 %'),
                        TextInput::make('subtitle')->label('Sous-titre')->placeholder('Sur toutes les marques'),
                        TextInput::make('button')
                            ->label('Texte du bouton (méga-menu)')
                            ->placeholder('Voir la collection')
                            ->helperText('Vide : « Voir la collection ». Le bouton mène à la page de la catégorie.')
                            ->maxLength(40),
                    ]),
            ]);
    }
}
