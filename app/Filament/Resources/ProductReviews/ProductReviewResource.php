<?php

namespace App\Filament\Resources\ProductReviews;

use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Customer reviews of products (verified purchases): moderated here, then shown on the product page; the product's
 * rating and review count follow the published reviews.
 */
class ProductReviewResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'avis client';

    protected static ?string $pluralModelLabel = 'avis clients';

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
        // Reviews come from customers only.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ProductReview::where('status', ReviewStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Avis à valider';
    }

    public static function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
        ];
    }
}
