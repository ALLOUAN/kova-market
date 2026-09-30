<?php

namespace App\Filament\Resources\Categories;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ListParentCategories;
use App\Filament\Resources\Categories\Pages\ListSubCategories;
use App\Filament\Resources\Categories\RelationManagers\ChildrenRelationManager;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

use function Filament\Support\original_request;

class CategoryResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'catégorie';

    protected static ?string $pluralModelLabel = 'catégories';

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
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    /**
     * Three sidebar entries: "Catégories" (all of them), then "Catégories parentes" and "Sous-catégories", each with
     * its list and its "Ajouter" form.
     */
    public static function getNavigationItems(): array
    {
        $pattern = static::getNavigationItemActiveRoutePattern();

        return [
            ...array_map(
                fn (NavigationItem $item) => $item->isActiveWhen(fn (): bool => original_request()->routeIs($pattern)
                    && ! self::isScreen(ListParentCategories::class, CreateCategory::PARENT_CATEGORY)
                    && ! self::isScreen(ListSubCategories::class, CreateCategory::SUB_CATEGORY)),
                parent::getNavigationItems(),
            ),
            NavigationItem::make('Catégories parentes')
                ->key(static::class.'.parents')
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedFolder)
                ->badge((string) Category::whereNull('parent_id')->count(), color: 'gray')
                ->sort(static::getNavigationSort())
                ->url(static::getUrl('parents'))
                ->isActiveWhen(fn (): bool => self::isScreen(ListParentCategories::class, CreateCategory::PARENT_CATEGORY)),
            NavigationItem::make('Sous-catégories')
                ->key(static::class.'.sub')
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedSquares2x2)
                ->badge((string) Category::whereNotNull('parent_id')->count(), color: 'gray')
                ->sort(static::getNavigationSort())
                ->url(static::getUrl('sub'))
                ->isActiveWhen(fn (): bool => self::isScreen(ListSubCategories::class, CreateCategory::SUB_CATEGORY)),
        ];
    }

    /**
     * Whether the page shown is that list, or the form opened from its "Ajouter" button.
     *
     * @param  class-string<ListRecords>  $list
     */
    private static function isScreen(string $list, string $type): bool
    {
        $request = original_request();

        return $request->routeIs($list::getRouteName())
            || ($request->routeIs(CreateCategory::getRouteName()) && $request->query('type') === $type);
    }

    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'parents' => ListParentCategories::route('/parentes'),
            'sub' => ListSubCategories::route('/sous-categories'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
