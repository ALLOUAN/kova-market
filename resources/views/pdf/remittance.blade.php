<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu de versement {{ $remittance->number() }}</title>
    {{-- Receipt of a courier's payment to the store (App\Services\Delivery\RemittanceReceipt). DomPDF: DejaVu Sans for
         the accents, inline styles only, the charter of the order receipt. --}}
    @php($contact = app(\App\Services\Storefront\StoreSettings::class)->contact())
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #0f1d33; }
        .head { width: 100%; border-bottom: 2px solid #c69e3e; padding-bottom: 10px; }
        .head td { vertical-align: top; border: none; padding: 0; }
        .head img { height: 46px; }
        .store { text-align: right; font-size: 9.5px; color: #3f4a5a; line-height: 1.5; }
        h1 { font-size: 20px; margin: 18px 0 2px; color: #021732; }
        .muted { color: #667085; }
        .stamp { display: inline-block; margin-top: 6px; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .stamp.paid { background: #e5f3eb; color: #0a502c; border: 1px solid #0e7d42; }
        .stamp.cancelled { background: #fdeceb; color: #8a241c; border: 1px solid #c0362c; }
        .boxes { width: 100%; margin-top: 16px; }
        .boxes td { width: 50%; vertical-align: top; border: none; padding: 10px 12px; background: #f4f6fa; }
        .boxes td + td { border-left: 6px solid #fff; }
        .boxes strong.label { display: block; color: #021732; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .items th { background: #021732; color: #fff; padding: 7px 8px; text-align: left; font-size: 9.5px; }
        .items td { border-bottom: 1px solid #e3e8ef; padding: 7px 8px; vertical-align: top; }
        .right { text-align: right; }
        table.totals { width: 46%; margin-left: 54%; border-collapse: collapse; margin-top: 10px; }
        .totals td { padding: 4px 8px; }
        .totals .grand td { border-top: 2px solid #021732; font-size: 13px; font-weight: bold; color: #021732; padding-top: 7px; }
        .signatures { width: 100%; margin-top: 34px; }
        .signatures td { width: 50%; border: none; padding: 0 12px 0 0; vertical-align: top; }
        .signatures .line { margin-top: 42px; border-top: 1px solid #98a2b3; padding-top: 4px; font-size: 9px; color: #667085; }
        .foot { margin-top: 26px; padding-top: 10px; border-top: 1px solid #e3e8ef; font-size: 9px; color: #667085; text-align: center; line-height: 1.6; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                @if (is_file(public_path('assets/images/logo/kova-logo.png')))
                    <img src="{{ public_path('assets/images/logo/kova-logo.png') }}" alt="{{ config('storefront.name') }}">
                @else
                    <strong style="font-size: 18px; color: #021732">{{ config('storefront.name') }}</strong>
                @endif
            </td>
            <td class="store">
                <strong style="color: #021732">{{ config('storefront.name') }}</strong><br>
                {{ $contact['address'] }}<br>
                {{ $contact['phone'] }}<br>
                {{ $contact['email'] }}
            </td>
        </tr>
    </table>

    <h1>Reçu de versement</h1>
    <p class="muted" style="margin: 0">
        Versement <strong style="color: #0f1d33">{{ $remittance->number() }}</strong> reçu le {{ $remittance->received_at->format('d/m/Y à H:i') }}
    </p>
    @if ($remittance->isCancelled())
        <span class="stamp cancelled">VERSEMENT ANNULÉ LE {{ $remittance->cancelled_at->format('d/m/Y') }}</span>
    @else
        <span class="stamp paid">REÇU : @money($remittance->amount)</span>
    @endif

    <table class="boxes">
        <tr>
            <td>
                <strong class="label">Livreur</strong>
                {{ $remittance->courier->name() }}<br>
                {{ $remittance->courier->formattedPhone() }}
            </td>
            <td>
                <strong class="label">Versement</strong>
                Mode : {{ $remittance->method->getLabel() }}<br>
                @if ($remittance->reference)
                    Référence : {{ $remittance->reference }}<br>
                @endif
                Reçu par : {{ $remittance->receivedBy?->name ?? '—' }}
                @if ($remittance->note)
                    <br>Note : {{ $remittance->note }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Commande</th>
                <th>Livrée à</th>
                <th class="right">Encaissé</th>
                <th class="right">Couvert par ce versement</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($remittance->orders as $order)
                <tr>
                    <td><strong>{{ $order->number }}</strong></td>
                    <td>{{ $order->commune_name }}</td>
                    <td class="right">@money($order->cash_collected)</td>
                    <td class="right">@money($order->pivot->amount)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="grand">
            <td>Total versé</td>
            <td class="right">@money($remittance->amount)</td>
        </tr>
        @if ($remittance->balance_after !== null)
            <tr>
                <td class="muted">Reste dû après ce versement</td>
                <td class="right muted">@money($remittance->balance_after)</td>
            </tr>
        @endif
    </table>

    @if ($remittance->isCancelled())
        <p style="margin-top: 16px; color: #8a241c">Motif de l’annulation : {{ $remittance->cancel_reason }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="line">Signature du livreur</div></td>
            <td><div class="line">Signature de {{ config('storefront.name') }}</div></td>
        </tr>
    </table>

    <div class="foot">
        {{ config('storefront.name') }} · Reçu édité le {{ now()->format('d/m/Y à H:i') }} · À conserver par le livreur et par la boutique.
    </div>
</body>
</html>
