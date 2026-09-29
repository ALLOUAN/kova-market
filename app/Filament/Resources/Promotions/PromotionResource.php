<?php

namespace App\Filament\Resources\Promotions;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Resources\Promotions\Schemas\PromotionForm;
use App\Filament\Resources\Promotions\Tables\PromotionsTable;
use App\Models\Promotion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Advertising campaigns shown in the "Offres spéciales" side panel (existing `promotions` table, decision C-06).
 */
class PromotionResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    // Named as on the storefront: the cards of the "Offres spéciales" side panel.
    protected static ?string $navigationLabel = 'Offres spéciales';

    protected static ?string $breadcrumb = 'Offres spéciales';

    protected static ?string $modelLabel = 'offre spéciale';

    protected static ?string $pluralModelLabel = 'offres spéciales';

    protected static ?string $recordTitleAttribute = 'title';

    protected static function managePermission(): Permission
    {
        return Permission::ManagePromotions;
    }

    public static function form(Schema $schema): Schema
    {
        return PromotionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromotionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }
}
