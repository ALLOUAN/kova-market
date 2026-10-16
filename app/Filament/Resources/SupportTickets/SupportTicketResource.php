<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\Permission;
use App\Enums\SupportTicketStatus;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Assistance requests sent from the customer area, answered here: the answer appears in the customer's area and
 * reaches them by e-mail.
 */
class SupportTicketResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Assistance';

    protected static ?string $modelLabel = 'demande d’assistance';

    protected static ?string $pluralModelLabel = 'demandes d’assistance';

    protected static ?string $recordTitleAttribute = 'title';

    protected static function managePermission(): Permission
    {
        return Permission::ManageOrders;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Requests waiting for the team.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = SupportTicket::where('status', SupportTicketStatus::Open)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
