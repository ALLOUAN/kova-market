<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Payment;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PaymentInfolist
{
    /** What each journal line means (see OnlinePayments). */
    private const EVENTS = [
        'initiated' => 'Client envoyé vers CinetPay',
        'initiation_refused' => 'Initialisation refusée par CinetPay',
        'notification' => 'Notification CinetPay reçue',
        'notification_refused' => 'Notification refusée (jeton invalide)',
        'pending' => 'Paiement en cours chez CinetPay',
        'succeeded' => 'Paiement confirmé par CinetPay',
        'echoue' => 'Paiement échoué',
        'annule' => 'Paiement annulé',
        'refunded' => 'Remboursement enregistré',
    ];

    private const SOURCES = [
        'notification' => 'notification',
        'return' => 'retour du client',
        'confirmation' => 'page de confirmation',
        'back-office' => 'back-office',
        'sweep' => 'vérification automatique',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Paiement')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('amount')
                            ->label('Montant')
                            ->weight('bold')
                            ->formatStateUsing(fn (Payment $record) => Money::format($record->amount))
                            ->belowContent(fn (Payment $record) => $record->order && $record->amount !== $record->order->total
                                ? 'Commande de '.Money::format($record->order->total).', arrondie au multiple de 5 FCFA supérieur'
                                : null),
                        TextEntry::make('merchant_transaction_id')->label('Référence KOVA')->copyable()->fontFamily('mono'),
                        TextEntry::make('gateway_transaction_id')->label('Référence CinetPay')->copyable()->placeholder('—')->fontFamily('mono'),
                        TextEntry::make('operator')->label('Moyen de paiement')->placeholder('—'),
                        TextEntry::make('payer_phone')->label('Téléphone payeur')->placeholder('—'),
                        TextEntry::make('created_at')->label('Lancé le')->dateTime('d/m/Y à H:i'),
                        TextEntry::make('paid_at')->label('Payé le')->dateTime('d/m/Y à H:i')->placeholder('—'),
                        TextEntry::make('failure_reason')->label('Motif de l’échec')->placeholder('—')->columnSpanFull()
                            ->visible(fn (Payment $record) => filled($record->failure_reason)),
                        TextEntry::make('refunded_at')
                            ->label('Remboursé le')
                            ->dateTime('d/m/Y à H:i')
                            ->belowContent(fn (Payment $record) => collect([$record->refundedBy?->name, $record->refund_reason])->filter()->join(' · ') ?: null)
                            ->visible(fn (Payment $record) => $record->refunded_at !== null),
                    ]),
                Section::make('Commande')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('order.number')
                            ->label('Numéro')
                            ->weight('bold')
                            ->url(fn (Payment $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                        TextEntry::make('order.customer_name')->label('Client')
                            ->belowContent(fn (Payment $record) => $record->order?->formattedPhone()),
                        TextEntry::make('order.total')->label('Total')->formatStateUsing(fn (int $state) => Money::format($state)),
                        TextEntry::make('order.status')->label('Statut de la commande')->badge(),
                        TextEntry::make('order.payment_status')->label('Paiement de la commande')->badge(),
                    ]),
                Section::make('Journal')
                    ->description('Ce qui s’est passé, et ce que CinetPay a répondu.')
                    ->columnSpan(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('events')
                            ->hiddenLabel()
                            ->state(fn (Payment $record) => array_reverse($record->events ?? []))
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn ($state) => self::describe((array) $state))
                            ->placeholder('Aucun évènement'),
                    ]),
            ]);
    }

    /**
     * One journal line in words: "29/09/2026 10:05 — Paiement confirmé par CinetPay (retour du client, code 100 SUCCESS)".
     *
     * @param  array<string, mixed>  $event
     */
    public static function describe(array $event): string
    {
        $details = collect([
            isset($event['source']) ? (self::SOURCES[$event['source']] ?? $event['source']) : null,
            isset($event['code']) ? trim('code '.$event['code'].' '.($event['status'] ?? '')) : null,
            isset($event['details']) ? 'détail '.$event['details'] : null,
            isset($event['transaction_id']) ? 'transaction '.$event['transaction_id'] : null,
            ! empty($event['rounded_by']) ? 'arrondi de +'.$event['rounded_by'].' FCFA' : null,
        ])->filter()->join(', ');

        $at = isset($event['at']) ? Carbon::parse($event['at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '';
        $label = self::EVENTS[$event['event'] ?? ''] ?? ($event['event'] ?? '?');

        return trim("{$at} — {$label}".($details !== '' ? " ({$details})" : ''), ' —');
    }
}
