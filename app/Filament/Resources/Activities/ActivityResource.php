<?php

namespace App\Filament\Resources\Activities;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit trail of the back-office actions (F-110).
 */
class ActivityResource extends Resource
{
    use AuthorizesWithPermission;

    public const SUBJECT_LABELS = [
        Product::class => 'Produit',
        Category::class => 'Catégorie',
        Brand::class => 'Marque',
        Collection::class => 'Collection',
        Promotion::class => 'Campagne',
        Banner::class => 'Bannière',
        Page::class => 'Page',
        Faq::class => 'Question',
        User::class => 'Compte',
    ];

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'action';

    protected static ?string $pluralModelLabel = 'journal d’audit';

    protected static function managePermission(): Permission
    {
        return Permission::ViewActivityLog;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function subjectLabel(?string $type): string
    {
        return self::SUBJECT_LABELS[$type] ?? ($type ? class_basename($type) : 'Paramètres');
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
