<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\NewsletterSubscribers\Widgets\NewsletterSubscribersOverview;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected static ?string $title = 'Newsletter';

    /**
     * The header band (NewsletterSubscribersOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [NewsletterSubscribersOverview::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter les abonnés actifs (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Abonnés')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('unsubscribed_at'))
                ->badge(fn () => NewsletterSubscriber::active()->count() ?: null),
            'unsubscribed' => Tab::make('Désinscrits')->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('unsubscribed_at')),
            'all' => Tab::make('Tous'),
        ];
    }

    /**
     * Active subscribers only, whatever the tab: the file is meant for sending. Semicolons and a BOM for Excel; the
     * unsubscribe link is included for the e-mailing tool.
     */
    private function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['E-mail', 'Inscrit depuis', 'Inscrit le', 'Lien de désinscription'], ';');

            NewsletterSubscriber::active()->orderBy('subscribed_at')->lazy()->each(fn (NewsletterSubscriber $subscriber) => fputcsv($out, [
                $subscriber->email,
                NewsletterSubscriber::SOURCES[$subscriber->source] ?? $subscriber->source,
                $subscriber->subscribed_at->format('d/m/Y H:i'),
                route('newsletter.unsubscribe', $subscriber),
            ], ';'));

            fclose($out);
        }, 'abonnes-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
