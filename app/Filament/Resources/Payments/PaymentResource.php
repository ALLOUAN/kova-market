<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Payments space (F-066, F-067): every online payment attempt across orders, what CinetPay answered, the takings,
 * the payments to check or refund, and an export to reconcile with CinetPay's statements. Payments are created by
 * the checkout only; the back-office checks them and records refunds.
 */
class PaymentResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    protected static ?string $recordTitleAttribute = 'merchant_transaction_id';

    protected static function managePermission(): Permission
    {
        return Permission::ManageOrders;
    }

    protected static function viewPermission(): Permission
    {
        return Permission::ViewOrders;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Money received on cancelled orders, waiting for its refund.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Payment::query()->toRefund()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Paiements à rembourser';
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }
}
