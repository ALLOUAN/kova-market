<?php

namespace App\Filament\Resources\Products;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\Products\RelationManagers\StockAlertsRelationManager;
use App\Filament\Resources\Products\RelationManagers\StockMovementsRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'produit';

    protected static ?string $pluralModelLabel = 'produits';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function managePermission(): Permission
    {
        return Permission::ManageCatalog;
    }

    protected static function viewPermission(): Permission
    {
        return Permission::ViewCatalog;
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    /**
     * Packs have their own screen (Promotions › Packs): their stock comes from their components.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_bundle', false);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
            StockMovementsRelationManager::class,
            StockAlertsRelationManager::class,
            ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
