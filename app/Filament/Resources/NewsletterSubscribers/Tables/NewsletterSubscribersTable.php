<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('subscribed_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('source')->label('Inscrit depuis')->formatStateUsing(fn (string $state) => NewsletterSubscriber::SOURCES[$state] ?? $state),
                TextColumn::make('subscribed_at')->label('Inscrit le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (NewsletterSubscriber $record) => $record->isActive() ? 'Abonné' : 'Désinscrit')
                    ->color(fn (string $state) => $state === 'Abonné' ? 'success' : 'gray')
                    ->description(fn (NewsletterSubscriber $record) => $record->unsubscribed_at ? 'le '.$record->unsubscribed_at->format('d/m/Y') : null),
            ])
            ->filters([
                SelectFilter::make('source')->label('Inscrit depuis')->options(NewsletterSubscriber::SOURCES),
            ])
            ->recordActions([
                Action::make('unsubscribe')
                    ->label('Désinscrire')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('À faire quand la personne vous le demande par un autre moyen (téléphone, message).')
                    ->visible(fn (NewsletterSubscriber $record) => $record->isActive())
                    ->action(function (NewsletterSubscriber $record): void {
                        $record->unsubscribe();
                        Notification::make()->title('Adresse désinscrite')->success()->send();
                    }),
                DeleteAction::make()
                    ->label('Effacer')
                    ->modalDescription('Efface l’adresse et la trace de son consentement, par exemple à la demande de la personne (droit à l’effacement). Pour arrêter seulement les envois, utilisez « Désinscrire ».'),
            ])
            ->emptyStateHeading('Aucun abonné pour le moment')
            ->emptyStateDescription('Les visiteurs s’inscrivent depuis le pied de page du site ou la fenêtre d’invitation.');
    }
}
