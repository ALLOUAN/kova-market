<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $paid ? 'Reçu de paiement' : 'Reçu de commande' }} {{ $order->number }}</title>
    {{-- Customer receipt (App\Services\Orders\OrderReceipt). DomPDF: DejaVu Sans for the accents, inline styles only,
         the KOVA MARKET charter (night blue, gold line, green for "paid"). --}}
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
        .stamp.due { background: #fdf3e2; color: #7a4d0b; border: 1px solid #a86b12; }
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
        .payment { margin-top: 18px; padding: 10px 12px; border: 1px solid #e3e8ef; border-left: 4px solid #0e7d42; }
        .payment.due { border-left-color: #a86b12; }
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

    <h1>{{ $paid ? 'Reçu de paiement' : 'Reçu de commande' }}</h1>
    <p class="muted" style="margin: 0">
        Commande <strong style="color: #0f1d33">{{ $order->number }}</strong> passée le {{ $order->created_at->format('d/m/Y à H:i') }}
    </p>
    @if ($order->status === \App\Enums\OrderStatus::Cancelled)
        <span class="stamp cancelled">COMMANDE ANNULÉE{{ $order->payment_status === \App\Enums\PaymentStatus::Refunded ? ' · REMBOURSÉE' : '' }}</span>
    @elseif ($paid)
        <span class="stamp paid">PAYÉ{{ $paidAt ? ' LE '.$paidAt->format('d/m/Y') : '' }}{{ $order->payment_status === \App\Enums\PaymentStatus::Refunded ? ' · REMBOURSÉ' : '' }}</span>
    @else
        <span class="stamp due">À PAYER : @money($order->total)</span>
    @endif

    <table class="boxes">
        <tr>
            <td>
                <strong class="label">Client</strong>
                {{ $order->customer_name }}<br>
                {{ $order->formattedPhone() }}@if ($order->email)<br>{{ $order->email }}@endif
            </td>
            <td>
                <strong class="label">Livraison</strong>
                {{ $order->district }}, {{ $order->commune_name }}@if ($order->landmark)<br>Repère : {{ $order->landmark }}@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Article</th>
                <th class="right" style="width: 18%">Prix unitaire</th>
                <th class="right" style="width: 8%">Qté</th>
                <th class="right" style="width: 18%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if ($item->variant_label)<br><span class="muted">{{ $item->variant_label }}</span>@endif
                        @if ($item->contentsSummary())<br><span class="muted">{{ $item->contentsSummary() }}</span>@endif
                        <br><span class="muted">Réf. {{ $item->sku }}</span>
                    </td>
                    <td class="right">@money($item->unit_price)</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">@money($item->line_total)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total</td><td class="right">@money($order->subtotal)</td></tr>
        @if ($order->discount > 0)
            <tr><td>Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="right">−@money($order->discount)</td></tr>
        @endif
        <tr><td>Livraison</td><td class="right">{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</td></tr>
        <tr class="grand"><td>Total{{ $paid ? ' payé' : '' }}</td><td class="right">@money($order->total)</td></tr>
    </table>

    <div @class(['payment', 'due' => ! $paid])>
        <strong style="color: #021732">Paiement :</strong> {{ $order->payment_method->getLabel() }} — {{ $order->payment_status->getLabel() }}
        @if ($paid && $payment)
            <br><span class="muted">
                Payé en ligne{{ $payment->operatorLabel() ? ' par '.$payment->operatorLabel() : '' }}{{ $payment->paid_at ? ' le '.$payment->paid_at->format('d/m/Y à H:i') : '' }}.
                Référence KOVA : {{ $payment->merchant_transaction_id }}@if ($payment->gateway_transaction_id) · Référence CinetPay : {{ $payment->gateway_transaction_id }}@endif
            </span>
        @elseif ($paid)
            <br><span class="muted">Payé à la livraison{{ $paidAt ? ' le '.$paidAt->format('d/m/Y') : '' }}.</span>
        @elseif ($order->payment_method->isOnline())
            <br><span class="muted">En attente du paiement en ligne.</span>
        @else
            <br><span class="muted">À régler au livreur à la réception, en espèces ou par Mobile Money.</span>
        @endif
    </div>

    <div class="foot">
        Merci pour votre confiance. Pour toute question sur cette commande : {{ $contact['phone'] }} · {{ $contact['email'] }}<br>
        Suivi de la commande : {{ route('tracking.show') }} (numéro {{ $order->number }})<br>
        Reçu émis le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
