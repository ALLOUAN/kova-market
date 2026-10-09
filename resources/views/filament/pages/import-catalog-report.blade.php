{{-- Result of the analysis (preview) or of the import of a catalog CSV (F-104), as key figure cards (.kd-*) and the
     refused lines in a table. --}}
@php
    $refused = count($report['errors']);
    $figures = [
        ['Lignes lues', $report['rows'], 'heroicon-o-document-text', 'navy'],
        ['Produits créés', $report['products_created'], 'heroicon-o-sparkles', 'orange'],
        ['Variantes ajoutées', $report['variants_created'], 'heroicon-o-plus-circle', 'green'],
        ['Variantes mises à jour', $report['variants_updated'], 'heroicon-o-arrow-path', 'gold'],
        ['Lignes refusées', $refused, $refused > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle', $refused > 0 ? 'red' : 'green'],
    ];
@endphp
<div class="kd kl ki-report">
    <header @class(['ki-report__head', 'is-preview' => $report['preview']])>
        <x-filament::icon :icon="$report['preview'] ? 'heroicon-o-magnifying-glass' : 'heroicon-o-check-badge'" class="ki-report__icon" />
        <div>
            <h2 class="ki-report__title">{{ $report['preview'] ? 'Analyse du fichier' : 'Import terminé' }}</h2>
            <p class="ki-report__lead">
                @if ($report['preview'])
                    Aperçu seulement, rien n’est encore enregistré. {{ $report['imported'] }} ligne(s) prête(s) à importer{{ $refused ? ', '.$refused.' seront ignorée(s)' : '' }} : confirmez avec « Importer » en haut de la page.
                @else
                    {{ $report['imported'] }} ligne(s) enregistrée(s){{ $refused ? ', '.$refused.' refusée(s)' : '' }}.
                @endif
            </p>
        </div>
    </header>

    <section class="kl-kpis" aria-label="Résultat">
        @foreach ($figures as [$label, $value, $icon, $tone])
            <div @class(['kd-kpi', 'kl-kpi--alert' => $tone === 'red'])>
                <span class="kd-kpi__icon kd-tone-{{ $tone === 'red' ? 'orange' : $tone }} @if ($tone === 'red') kl-tone-red @endif"><x-filament::icon :icon="$icon" /></span>
                <span class="kd-kpi__label">{{ $label }}</span>
                <span class="kd-kpi__value">{{ $value }}</span>
            </div>
        @endforeach
    </section>

    @if ($refused)
        <div class="ki-errors">
            <h3 class="ki-errors__title">Lignes refusées{{ $report['preview'] ? ' (elles seront ignorées)' : '' }}</h3>
            <table class="ki-errors__table">
                <thead>
                    <tr><th>Ligne</th><th>Raison</th></tr>
                </thead>
                <tbody>
                    @foreach ($report['errors'] as $line => $reason)
                        <tr><td class="ki-errors__line">{{ $line }}</td><td>{{ $reason }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
