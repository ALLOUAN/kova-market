{{-- Latest money movements of a courier (App\Filament\Resources\Couriers\Widgets\CourierFinanceHistory). Styles in
     public/assets/admin/kova-admin.css (the panel has no Tailwind build of its own). --}}
<x-filament-widgets::widget>
    <x-filament::section heading="Historique des opérations" description="Encaissements à la livraison et versements à la boutique, du plus récent au plus ancien.">
        @if ($entries->isEmpty())
            <p class="kova-ledger__empty">Aucune opération pour le moment.</p>
        @else
            <ul class="kova-ledger">
                @foreach ($entries as $entry)
                    <li @class(['kova-ledger__row', 'is-cancelled' => $entry['cancelled']])>
                        <div class="kova-ledger__text">
                            <p class="kova-ledger__label">{{ $entry['label'] }}</p>
                            <p class="kova-ledger__meta">
                                {{ $entry['date']->format('d/m/Y à H:i') }}
                                @if ($entry['note'])
                                    · {{ $entry['note'] }}
                                @endif
                            </p>
                        </div>
                        <span @class(['kova-ledger__amount', $entry['amount'] > 0 ? 'is-in' : 'is-out'])>
                            {{ $entry['amount'] > 0 ? '+' : '−' }} @money(abs($entry['amount']))
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
