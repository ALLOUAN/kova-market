<?php

namespace App\Filament\Resources\ProductAttributes;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\ProductAttributes\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\EditProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\ListProductAttributes;
use App\Filament\Resources\ProductAttributes\Schemas\ProductAttributeForm;
use App\Filament\Resources\ProductAttributes\Tables\ProductAttributesTable;
use App\Models\ProductAttribute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductAttributeResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = ProductAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'attribut';

    protected static ?string $pluralModelLabel = 'attributs de variantes';

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
        return ProductAttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductAttributesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'edit' => EditProductAttribute::route('/{record}/edit'),
        ];
    }
}
