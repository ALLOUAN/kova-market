<?php

namespace App\Filament\Resources\NewsletterCampaigns\RelationManagers;

use App\Models\NewsletterCampaignRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Who received a campaign, and why some did not (error of the mail server, unsubscribed before the sending).
 */
class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Destinataires';

    protected static ?string $modelLabel = 'destinataire';

    private const STATUSES = [
        NewsletterCampaignRecipient::PENDING => 'En attente',
        NewsletterCampaignRecipient::SENT => 'Envoyé',
        NewsletterCampaignRecipient::FAILED => 'Échec',
        NewsletterCampaignRecipient::SKIPPED => 'Ignoré',
    ];

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->recipients_count > 0;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('subscriber'))
            ->defaultSort('id')
            ->columns([
                TextColumn::make('subscriber.email')->label('E-mail')->searchable()->placeholder('Adresse effacée'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        NewsletterCampaignRecipient::SENT => 'success',
                        NewsletterCampaignRecipient::FAILED => 'danger',
                        NewsletterCampaignRecipient::PENDING => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (NewsletterCampaignRecipient $record) => $record->error),
                TextColumn::make('sent_at')->label('Envoyé le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(self::STATUSES),
            ])
            ->emptyStateHeading('Aucun destinataire');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
