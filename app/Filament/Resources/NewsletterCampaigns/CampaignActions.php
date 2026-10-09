<?php

namespace App\Filament\Resources\NewsletterCampaigns;

use App\Enums\CampaignStatus;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\CampaignSender;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * What can be done with a campaign: look at it, try it on one address, send it, schedule it, stop it, copy it.
 */
class CampaignActions
{
    public static function preview(): Action
    {
        return Action::make('preview')
            ->label('Aperçu')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn (NewsletterCampaign $record) => $record->subject)
            ->modalDescription('L’e-mail tel que les abonnés le recevront.')
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalContent(fn (NewsletterCampaign $record) => view('filament.newsletter.preview', [
                'html' => (new NewsletterCampaignMail($record))->render(),
            ]));
    }

    public static function sendTest(): Action
    {
        return Action::make('sendTest')
            ->label('Envoyer un test')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->modalDescription('Recevez la campagne sur une adresse pour la relire avant l’envoi. Son objet commence par [TEST].')
            ->modalSubmitActionLabel('Envoyer le test')
            ->schema([
                TextInput::make('email')->label('Adresse')->email()->required()->default(fn () => auth()->user()?->email),
            ])
            ->action(function (NewsletterCampaign $record, array $data): void {
                app(CampaignSender::class)->sendTest($record, $data['email']);
                Notification::make()->title("Test envoyé à {$data['email']}")->success()->send();
            });
    }

    public static function sendNow(): Action
    {
        return Action::make('sendNow')
            ->label('Envoyer maintenant')
            ->icon('heroicon-o-rocket-launch')
            ->color('primary')
            ->visible(fn (NewsletterCampaign $record) => $record->isEditable())
            ->requiresConfirmation()
            ->modalHeading('Envoyer la campagne ?')
            ->modalDescription(fn () => 'Elle part à '.self::audience().'. Une fois lancée, elle ne peut plus être modifiée.')
            ->modalSubmitActionLabel('Envoyer')
            ->action(function (NewsletterCampaign $record): void {
                if (NewsletterSubscriber::active()->doesntExist()) {
                    Notification::make()->title('Aucun abonné actif : rien à envoyer.')->warning()->send();

                    return;
                }

                $count = app(CampaignSender::class)->launch($record, auth()->user());
                $record->refresh();

                // Without a queue (QUEUE_CONNECTION=sync) everything has already left; with one, it goes out in batches.
                $done = $record->status === CampaignStatus::Sent;
                Notification::make()
                    ->title($done ? "Campagne envoyée à {$record->sent_count} abonné(s)" : "Campagne lancée vers {$count} abonné(s)")
                    ->body(match (true) {
                        $done && $record->failed_count > 0 => "{$record->failed_count} envoi(s) en échec : voyez la liste des destinataires.",
                        $done => null,
                        default => 'Les e-mails partent par lots en quelques minutes.',
                    })
                    ->success()
                    ->send();
            });
    }

    public static function schedule(): Action
    {
        return Action::make('schedule')
            ->label(fn (NewsletterCampaign $record) => $record->status === CampaignStatus::Scheduled ? 'Changer la date' : 'Programmer')
            ->icon('heroicon-o-calendar-days')
            ->color('gray')
            ->visible(fn (NewsletterCampaign $record) => $record->isEditable())
            ->modalDescription(fn () => 'La campagne partira seule à la date choisie, vers '.self::audience().' à ce moment-là.')
            ->modalSubmitActionLabel('Programmer')
            ->schema([
                DateTimePicker::make('scheduled_at')
                    ->label('Envoyer le')
                    ->seconds(false)
                    ->minDate(now()->startOfDay())
                    ->default(fn (NewsletterCampaign $record) => $record->scheduled_at ?? now()->addDay()->setTime(9, 0))
                    ->required()
                    ->after('now'),
            ])
            ->action(function (NewsletterCampaign $record, array $data): void {
                app(CampaignSender::class)->schedule($record, Carbon::parse($data['scheduled_at']));
                Notification::make()->title('Campagne programmée le '.Carbon::parse($data['scheduled_at'])->translatedFormat('j F à H\hi'))->success()->send();
            });
    }

    public static function unschedule(): Action
    {
        return Action::make('unschedule')
            ->label('Annuler la programmation')
            ->icon('heroicon-o-calendar')
            ->color('gray')
            ->visible(fn (NewsletterCampaign $record) => $record->status === CampaignStatus::Scheduled)
            ->requiresConfirmation()
            ->modalDescription('La campagne redevient un brouillon : elle ne partira pas.')
            ->action(function (NewsletterCampaign $record): void {
                app(CampaignSender::class)->unschedule($record);
                Notification::make()->title('La campagne est de nouveau un brouillon')->success()->send();
            });
    }

    public static function stop(): Action
    {
        return Action::make('stop')
            ->label('Arrêter l’envoi')
            ->icon('heroicon-o-stop-circle')
            ->color('danger')
            ->visible(fn (NewsletterCampaign $record) => $record->status === CampaignStatus::Sending)
            ->requiresConfirmation()
            ->modalDescription('Les e-mails déjà partis ne peuvent pas être rappelés ; les autres ne seront pas envoyés.')
            ->action(function (NewsletterCampaign $record): void {
                app(CampaignSender::class)->stop($record, auth()->user());
                Notification::make()->title('Envoi arrêté')->success()->send();
            });
    }

    public static function duplicate(): Action
    {
        return Action::make('duplicate')
            ->label('Dupliquer')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->action(function (NewsletterCampaign $record) {
                $copy = $record->replicate(['status', 'scheduled_at', 'started_at', 'sent_at', 'recipients_count', 'sent_count', 'failed_count', 'skipped_count']);
                $copy->fill(['subject' => mb_substr('Copie de '.$record->subject, 0, 150), 'scheduled_at' => null, 'created_by' => auth()->id()]);
                $copy->status = CampaignStatus::Draft;
                $copy->save();

                return redirect(NewsletterCampaignResource::getUrl('edit', ['record' => $copy]));
            });
    }

    private static function audience(): string
    {
        $count = NewsletterSubscriber::active()->count();

        return $count.' abonné'.($count > 1 ? 's' : '').' actif'.($count > 1 ? 's' : '');
    }
}
