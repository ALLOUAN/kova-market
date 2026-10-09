<?php

namespace App\Filament\Resources\NewsletterCampaigns;

use App\Enums\CampaignStatus;
use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\NewsletterCampaigns\Pages\CreateNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Filament\Resources\NewsletterCampaigns\Pages\ViewNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\RelationManagers\RecipientsRelationManager;
use App\Filament\Resources\NewsletterCampaigns\Schemas\CampaignForm;
use App\Filament\Resources\NewsletterCampaigns\Tables\CampaignsTable;
use App\Models\NewsletterCampaign;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Newsletter campaigns by e-mail: written here, tried on one address, sent at once or at a date to the active
 * subscribers (App\Services\Newsletter\CampaignSender), followed while they go out.
 */
class NewsletterCampaignResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = NewsletterCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Campagnes e-mail';

    protected static ?string $modelLabel = 'campagne';

    protected static ?string $pluralModelLabel = 'campagnes e-mail';

    protected static ?string $recordTitleAttribute = 'subject';

    protected static function managePermission(): Permission
    {
        return Permission::ManagePromotions;
    }

    public static function getNavigationBadge(): ?string
    {
        $sending = NewsletterCampaign::query()->where('status', CampaignStatus::Sending)->count();

        return $sending > 0 ? (string) $sending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Campagnes en cours d’envoi';
    }

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    /**
     * Follow-up page of a campaign that has left: progress and counters, then the message as sent.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Envoi')
                    ->schema([
                        View::make('filament.newsletter.progress')->viewData(fn (NewsletterCampaign $record) => ['campaign' => $record->refresh()]),
                    ]),
                Section::make('Message')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subject')->label('Objet'),
                        TextEntry::make('preheader')->label('Ligne d’aperçu')->placeholder('—'),
                        TextEntry::make('button_label')->label('Bouton')->placeholder('Aucun')
                            ->belowContent(fn (NewsletterCampaign $record) => $record->button_url),
                        TextEntry::make('content')->label('Contenu')->html()->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterCampaigns::route('/'),
            'create' => CreateNewsletterCampaign::route('/create'),
            'view' => ViewNewsletterCampaign::route('/{record}'),
            'edit' => EditNewsletterCampaign::route('/{record}/edit'),
        ];
    }
}
