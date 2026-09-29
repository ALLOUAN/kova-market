<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $order->number }}</title>
    {{-- DomPDF: DejaVu Sans renders French accents; inline styles only. --}}
    {{-- KOVA MARKET charter: night blue titles and table heads, gold line, plain colours. --}}
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f1d33; }
        .brand { border-bottom: 2px solid #c69e3e; padding-bottom: 8px; margin-bottom: 8px; }
        .brand img { height: 42px; }
        h1 { font-size: 18px; margin: 6px 0 4px; color: #021732; }
        .muted { color: #667085; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e3e8ef; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #021732; color: #fff; border-color: #021732; }
        .right { text-align: right; }
        .boxes td { border: none; padding: 0 12px 0 0; width: 50%; }
        .boxes strong { color: #021732; }
        .totals td { border: none; padding: 2px 6px; }
        .totals .grand td { border-top: 2px solid #021732; font-size: 13px; color: #021732; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="brand">
        {{-- DomPDF reads the logo from disk (PNG, no network); the store name stands in for it if missing. --}}
        @if (is_file(public_path('assets/images/logo/kova-logo.png')))
            <img src="{{ public_path('assets/images/logo/kova-logo.png') }}" alt="{{ config('storefront.name') }}">
        @else
            <strong style="font-size: 16px; color: #021732">{{ config('storefront.name') }}</strong>
        @endif
    </div>
    <h1>Bon de commande</h1>
    <p class="muted">Commande <strong>{{ $order->number }}</strong> du {{ $order->created_at->format('d/m/Y à H:i') }} · Statut : {{ $order->status->getLabel() }}</p>

    <table class="boxes">
        <tr>
            <td>
                <strong>Client</strong><br>
                {{ $order->customer_name }}<br>
                {{ $order->formattedPhone() }}@if ($order->email)<br>{{ $order->email }}@endif
            </td>
            <td>
                <strong>Livraison</strong><br>
                {{ $order->district }}, {{ $order->commune_name }} ({{ $order->zone_name }})@if ($order->landmark)<br>Repère : {{ $order->landmark }}@endif
                @if ($order->note)<br>Note : {{ $order->note }}@endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Référence</th>
                <th class="right">Prix unitaire</th>
                <th class="right">Qté</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}@if ($item->variant_label)<br><span class="muted">{{ $item->variant_label }}</span>@endif@if ($item->contentsSummary())<br><span class="muted">{{ $item->contentsSummary() }}</span>@endif</td>
                    <td>{{ $item->sku }}</td>
                    <td class="right">@money($item->unit_price)</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">@money($item->line_total)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="right">Sous-total</td><td class="right" style="width: 120px">@money($order->subtotal)</td></tr>
        @if ($order->discount > 0)<tr><td class="right">Remise ({{ $order->coupon_code }})</td><td class="right">-@money($order->discount)</td></tr>@endif
        <tr><td class="right">Livraison</td><td class="right">{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</td></tr>
        <tr class="grand"><td class="right"><strong>Total</strong></td><td class="right"><strong>@money($order->total)</strong></td></tr>
        <tr><td class="right muted">{{ $order->payment_method->getLabel() }}</td><td class="right muted">{{ $order->payment_status->getLabel() }}</td></tr>
    </table>
</body>
</html>
