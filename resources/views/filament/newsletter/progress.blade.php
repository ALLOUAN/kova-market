{{-- Follow-up of a campaign (ViewNewsletterCampaign): progress and counters; polls every 5 s while it goes out.
     Styles in public/assets/admin/kova-admin.css (.kn-*). --}}
@php($sending = $campaign->status === \App\Enums\CampaignStatus::Sending)
<div @class(['kn', 'is-done' => $campaign->status === \App\Enums\CampaignStatus::Sent]) @if ($sending) wire:poll.5s @endif>
    <div class="kn-progress">
        <div class="kn-progress__head">
            <span>
                @if ($sending)
                    <span class="kn-live" aria-hidden="true"></span> Envoi en cours…
                @else
                    {{ $campaign->status->getLabel() }}
                @endif
            </span>
            <strong>{{ $campaign->progress() }} %</strong>
        </div>
        <div class="kn-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $campaign->progress() }}" aria-label="Avancement de l’envoi">
            <span style="width: {{ $campaign->progress() }}%"></span>
        </div>
    </div>
    {{-- Nothing handled two minutes after the launch: the queue that sends the e-mails is not running. --}}
    @if ($sending && $campaign->sent_count + $campaign->failed_count + $campaign->skipped_count === 0 && $campaign->started_at?->lte(now()->subMinutes(2)))
        <div class="kn-stalled" role="alert">
            <strong>Aucun e-mail n’est encore parti.</strong>
            Les e-mails sont envoyés par la file d’attente, qui ne semble pas tourner. Sur l’hébergement, vérifiez la tâche
            cron du planificateur ; en local, démarrez le site avec <code>demarrer-local.bat</code> (ou lancez
            <code>php artisan queue:work</code>). L’envoi reprendra tout seul, sans doublon.
        </div>
    @endif
    <dl class="kn-stats">
        <div><dt>Destinataires</dt><dd>{{ $campaign->recipients_count }}</dd></div>
        <div class="is-ok"><dt>Envoyés</dt><dd>{{ $campaign->sent_count }}</dd></div>
        <div @class(['is-bad' => $campaign->failed_count > 0])><dt>Échecs</dt><dd>{{ $campaign->failed_count }}</dd></div>
        <div><dt>Ignorés</dt><dd>{{ $campaign->skipped_count }}</dd></div>
    </dl>
    <p class="kn-meta">
        @if ($campaign->started_at) Lancée le {{ $campaign->started_at->format('d/m/Y à H:i') }} @endif
        @if ($campaign->sent_at) · terminée le {{ $campaign->sent_at->format('d/m/Y à H:i') }} @endif
        @if ($campaign->author) · par {{ $campaign->author->name }} @endif
        · Ignorés : désinscrits avant l’envoi ou envoi arrêté.
    </p>
</div>
