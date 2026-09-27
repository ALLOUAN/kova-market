<?php

namespace App\Filament\Resources\Bundles;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Bundles\Pages\CreateBundle;
use App\Filament\Resources\Bundles\Pages\EditBundle;
use App\Filament\Resources\Bundles\Pages\ListBundles;
use App\Filament\Resources\Bundles\Schemas\BundleForm;
use App\Filament\Resources\Bundles\Tables\BundlesTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Packs (F-093): products sold at one price and made of variants of other products. Their stock is what the
 * components allow; selling one takes each component's quantity.
 */
class BundleResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Product::class;

    protected static ?string $slug = 'packs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    protected static ?string $modelLabel = 'pack';

    protected static ?string $pluralModelLabel = 'packs';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function managePermission(): Permission
    {
        return Permission::ManagePromotions;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_bundle', true);
    }

    public static function form(Schema $schema): Schema
    {
        return BundleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BundlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBundles::route('/'),
            'create' => CreateBundle::route('/create'),
            'edit' => EditBundle::route('/{record}/edit'),
        ];
    }
}
