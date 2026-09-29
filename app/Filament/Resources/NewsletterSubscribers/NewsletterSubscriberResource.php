<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Newsletter sign-ups (footer, invitation window): the list to export into the e-mailing tool, unsubscribes kept as
 * proof. Addresses are never typed in by hand: the consent comes from the person.
 */
class NewsletterSubscriberResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?string $breadcrumb = 'Newsletter';

    protected static ?string $modelLabel = 'abonné';

    protected static ?string $pluralModelLabel = 'abonnés à la newsletter';

    protected static ?string $recordTitleAttribute = 'email';

    protected static function managePermission(): Permission
    {
        return Permission::ManagePromotions;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = NewsletterSubscriber::active()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Abonnés actifs';
    }

    public static function table(Table $table): Table
    {
        return NewsletterSubscribersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterSubscribers::route('/'),
        ];
    }
}
