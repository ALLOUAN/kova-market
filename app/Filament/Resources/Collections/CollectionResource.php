<?php

namespace App\Filament\Resources\Collections;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Collections\Schemas\CollectionForm;
use App\Filament\Resources\Collections\Tables\CollectionsTable;
use App\Models\Collection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Home page merchandising collections. They are referenced by slug in the code, so they can be edited
 * (title, end date, products and their order) but not created or deleted from the back-office.
 */
class CollectionResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'collection';

    protected static ?string $pluralModelLabel = 'collections de l’accueil';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function managePermission(): Permission
    {
        return Permission::ManageCatalog;
    }

    protected static function viewPermission(): Permission
    {
        return Permission::ViewCatalog;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}
