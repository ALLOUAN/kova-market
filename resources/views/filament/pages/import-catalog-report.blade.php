{{-- Result of the analysis (preview) or of the import of a catalog CSV (F-104). Inline styles: the panel has no
     custom theme, so only Filament's own CSS classes are available. --}}
<x-filament::section :heading="$report['preview'] ? 'Analyse du fichier (rien n’est encore enregistré)' : 'Résultat de l’import'">
    <dl style="display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 1rem; margin: 0">
        @foreach ([
            'Lignes lues' => $report['rows'],
            'Produits créés' => $report['products_created'],
            'Variantes ajoutées' => $report['variants_created'],
            'Variantes mises à jour' => $report['variants_updated'],
            'Lignes refusées' => count($report['errors']),
        ] as $label => $value)
            <div>
                <dt style="font-size: .875rem; opacity: .7">{{ $label }}</dt>
                <dd style="font-size: 1.5rem; font-weight: 600; margin: 0">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($report['errors'])
        <h3 style="margin: 1.5rem 0 .5rem; font-weight: 600">Lignes refusées{{ $report['preview'] ? ' (elles seront ignorées)' : '' }}</h3>
        <table style="width: 100%; font-size: .875rem; border-collapse: collapse">
            <thead>
                <tr style="text-align: left; opacity: .7">
                    <th style="padding: .25rem 1rem .25rem 0">Ligne</th>
                    <th style="padding: .25rem 0">Raison</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['errors'] as $line => $reason)
                    <tr style="border-top: 1px solid rgba(128, 128, 128, .25)">
                        <td style="padding: .25rem 1rem .25rem 0; vertical-align: top">{{ $line }}</td>
                        <td style="padding: .25rem 0">{{ $reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-filament::section>
