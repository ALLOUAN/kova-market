<?php

namespace App\Filament\Resources\NewsletterCampaigns\Tables;

use App\Enums\CampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\CampaignActions;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Models\NewsletterCampaign;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll(fn () => NewsletterCampaign::query()->where('status', CampaignStatus::Sending)->exists() ? '10s' : null)
            ->columns([
                TextColumn::make('subject')
                    ->label('Campagne')
                    ->weight('bold')
                    ->searchable()
                    ->limit(60)
                    ->description(fn (NewsletterCampaign $record) => $record->preheader ? mb_strimwidth($record->preheader, 0, 70, '…') : null),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('when')
                    ->label('Date')
                    ->state(fn (NewsletterCampaign $record) => match ($record->status) {
                        CampaignStatus::Scheduled => 'Prévue le '.$record->scheduled_at->format('d/m/Y à H:i'),
                        CampaignStatus::Sent, CampaignStatus::Cancelled => $record->sent_at?->format('d/m/Y à H:i'),
                        CampaignStatus::Sending => 'Depuis '.$record->started_at?->format('H:i'),
                        default => 'Modifiée le '.$record->updated_at->format('d/m/Y'),
                    }),
                TextColumn::make('recipients_count')
                    ->label('Envoi')
                    ->state(fn (NewsletterCampaign $record) => $record->recipients_count > 0 ? "{$record->sent_count} / {$record->recipients_count} envoyés" : '—')
                    ->description(fn (NewsletterCampaign $record) => match (true) {
                        $record->recipients_count === 0 => null,
                        $record->failed_count > 0 => "{$record->failed_count} échec(s) · {$record->progress()} %",
                        default => "{$record->progress()} %",
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(CampaignStatus::class),
            ])
            ->recordUrl(fn (NewsletterCampaign $record) => NewsletterCampaignResource::getUrl($record->isEditable() ? 'edit' : 'view', ['record' => $record]))
            ->recordActions([
                EditAction::make()->visible(fn (NewsletterCampaign $record) => $record->isEditable()),
                ViewAction::make()->label('Suivi')->visible(fn (NewsletterCampaign $record) => ! $record->isEditable()),
                ActionGroup::make([
                    CampaignActions::preview(),
                    CampaignActions::duplicate(),
                    DeleteAction::make()->visible(fn (NewsletterCampaign $record) => $record->status === CampaignStatus::Draft),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading('Aucune campagne pour le moment')
            ->emptyStateDescription('Écrivez votre premier e-mail aux abonnés de la newsletter : une offre, une nouveauté, un code promo.');
    }
}
