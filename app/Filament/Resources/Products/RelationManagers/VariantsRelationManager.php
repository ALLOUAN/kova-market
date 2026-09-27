<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockMovementReason;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\StockManager;
use App\Support\Money;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Variants of a product (F-035): attribute values, SKU, prices and stock. Stock is never typed over:
 * it changes through "Ajuster le stock", which records a stock movement (F-103).
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    protected static ?string $modelLabel = 'variante';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('attributeValues')
                    ->label('Caractéristiques de la variante')
                    ->helperText('Une valeur par attribut, par exemple « Couleur : Noir » et « Capacité : 128 Go ». Vide pour un modèle unique.')
                    ->relationship('attributeValues', 'value', fn (Builder $query) => $query->with('attribute')->orderBy('attribute_id')->orderBy('position'))
                    ->getOptionLabelFromRecordUsing(fn (AttributeValue $record) => $record->label())
                    ->multiple()
                    ->preload()
                    ->rule(fn (?ProductVariant $record): Closure => $this->uniqueCombinationRule($record))
                    ->columnSpanFull(),
                TextInput::make('sku')
                    ->label('Référence (SKU)')
                    ->required()
                    ->maxLength(64)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => $this->nextSku()),
                TextInput::make('low_stock_threshold')
                    ->label('Seuil d’alerte de stock')
                    ->helperText('Vide : '.config('storefront.product_card.limited_stock_threshold').' unités (réglage général).')
                    ->integer()
                    ->minValue(0),
                TextInput::make('price')
                    ->label('Prix de vente')
                    ->integer()
                    ->minValue(0)
                    ->suffix('FCFA')
                    ->required()
                    ->default(fn () => $this->getOwnerRecord()->defaultVariant?->price),
                TextInput::make('compare_at_price')
                    ->label('Prix barré')
                    ->integer()
                    ->minValue(0)
                    ->suffix('FCFA')
                    ->gt('price'),
                TextInput::make('opening_stock')
                    ->label('Stock initial')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->visibleOn('create'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('attributeValues'))
            ->recordTitle(fn (ProductVariant $record) => "{$record->sku} — {$record->label()}")
            ->columns([
                TextColumn::make('sku')->label('Référence')->searchable(),
                TextColumn::make('label')->label('Variante')->state(fn (ProductVariant $record) => $record->label()),
                TextColumn::make('price')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->description(fn (ProductVariant $record) => $record->compare_at_price ? 'au lieu de '.Money::format($record->compare_at_price) : null),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (ProductVariant $record) => match (true) {
                        $record->stock === 0 => 'danger',
                        $record->isLowOnStock() => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_default')->label('Par défaut')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une variante')
                    ->using(fn (array $data): Model => app(StockManager::class)->createVariant(
                        $this->getOwnerRecord(),
                        Arr::except($data, ['opening_stock', 'attributeValues']),
                        (int) $data['opening_stock'],
                        auth()->user(),
                    )),
            ])
            ->recordActions([
                Action::make('adjustStock')
                    ->label('Ajuster le stock')
                    ->icon('heroicon-o-arrows-up-down')
                    ->modalHeading(fn (ProductVariant $record) => "Stock de {$record->sku}")
                    ->modalDescription(fn (ProductVariant $record) => "Stock actuel : {$record->stock}. Indiquez la quantité réellement disponible.")
                    ->schema([
                        TextInput::make('counted')
                            ->label('Nouvelle quantité en stock')
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->default(fn (ProductVariant $record) => $record->stock),
                        Select::make('reason')
                            ->label('Motif')
                            ->options(StockMovementReason::manualOptions())
                            ->default(StockMovementReason::Adjustment->value)
                            ->required(),
                        TextInput::make('note')
                            ->label('Commentaire')
                            ->placeholder('Inventaire, casse, réception fournisseur…')
                            ->maxLength(255),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        $movement = app(StockManager::class)->setTo(
                            $record,
                            (int) $data['counted'],
                            StockMovementReason::from($data['reason']),
                            auth()->user(),
                            $data['note'] ?? null,
                        );

                        Notification::make()
                            ->title($movement ? "Stock mis à jour : {$movement->stock_after}" : 'Stock inchangé')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                // The default variant stays; a variant with sales or adjustments keeps its history.
                DeleteAction::make()
                    ->hidden(fn (ProductVariant $record) => $record->is_default
                        || $record->stockMovements()->where('reason', '!=', StockMovementReason::Initial)->exists()),
            ]);
    }

    /**
     * One value per attribute, and no two variants of the product with the same combination.
     */
    private function uniqueCombinationRule(?ProductVariant $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $values = AttributeValue::query()->whereKey((array) $value)->get();

            if ($values->pluck('attribute_id')->duplicates()->isNotEmpty()) {
                $fail('Choisissez une seule valeur par attribut.');

                return;
            }

            $wanted = $values->pluck('id')->sort()->values()->all();

            $taken = $this->getOwnerRecord()->variants()
                ->with('attributeValues')
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                ->get()
                ->contains(fn (ProductVariant $variant) => $variant->attributeValues->pluck('id')->sort()->values()->all() === $wanted);

            if ($taken) {
                $fail('Une variante avec ces caractéristiques existe déjà pour ce produit.');
            }
        };
    }

    private function nextSku(): string
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return StockManager::defaultSku($product).'-'.($product->variants()->count() + 1);
    }
}
