{{--
    Newsletter campaign e-mail (App\Mail\NewsletterCampaignMail): tables and inline styles only, the only layout
    Gmail, Outlook and phone mail apps all read the same way. 600 px wide, full width on a phone.
    Logo and cover are embedded ($message->embed) when sent, and plain URLs in the back-office preview.
--}}
@php
    $image = fn (?string $path, string $fallback) => isset($message) && $path && is_file($path) ? $message->embed($path) : $fallback;
    $logo = $image($logoPath, asset('assets/images/logo/kova-logo.png'));
    $cover = $campaign->image ? $image($coverPath, asset($campaign->image)) : null;
    $store = config('storefront.name');
    $font = "font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
@endphp
<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $campaign->subject }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .kn-container { width: 100% !important; }
            .kn-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .kn-col { display: block !important; width: 100% !important; padding: 0 0 10px !important; }
            .kn-title { font-size: 24px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#eef1f6;{{ $font }}">
    {{-- Preview line shown next to the subject by mail apps, hidden in the message. --}}
    @if ($campaign->preheader)
        <div style="display:none;max-height:0;max-width:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;color:#eef1f6">{{ $campaign->preheader }}&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef1f6">
        <tr>
            <td align="center" style="padding:28px 12px">
                <table role="presentation" class="kn-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">

                    @if ($isTest)
                        <tr>
                            <td style="padding:0 0 12px">
                                <div style="padding:10px 14px;border-radius:10px;background:#fff4ec;border:1px solid #ffcfb0;color:#a33400;font-size:13px;line-height:1.5;{{ $font }}">
                                    <strong>E-mail de test</strong> : les abonnés recevront ce message sans ce bandeau.
                                </div>
                            </td>
                        </tr>
                    @endif

                    {{-- Header: the logo on white, the gold line of the brand underneath. --}}
                    <tr>
                        <td align="center" style="background:#ffffff;border-radius:16px 16px 0 0;padding:26px 24px 22px;border-bottom:3px solid #c69e3e">
                            <a href="{{ url('/') }}" style="text-decoration:none">
                                <img src="{{ $logo }}" width="170" alt="{{ $store }}" style="display:block;width:170px;max-width:170px;height:auto;border:0;outline:none">
                            </a>
                        </td>
                    </tr>

                    @if ($cover)
                        <tr>
                            <td style="background:#ffffff;padding:0">
                                <img src="{{ $cover }}" width="600" alt="" style="display:block;width:100%;max-width:600px;height:auto;border:0">
                            </td>
                        </tr>
                    @endif

                    {{-- Message. --}}
                    <tr>
                        <td class="kn-pad" style="background:#ffffff;padding:34px 40px 10px;{{ $font }}">
                            <h1 class="kn-title" style="margin:0 0 18px;font-size:28px;line-height:1.25;font-weight:800;color:#021732;letter-spacing:-0.3px;{{ $font }}">{{ $campaign->subject }}</h1>
                            <div style="font-size:16px;line-height:1.65;color:#3f4a5a;{{ $font }}">
                                {!! $body !!}
                            </div>
                        </td>
                    </tr>

                    @if ($campaign->button_label && $campaign->button_url)
                        <tr>
                            <td class="kn-pad" align="center" style="background:#ffffff;padding:14px 40px 34px">
                                {{-- "Bulletproof" button: a coloured cell, so it shows even without images. --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td align="center" bgcolor="#cc4400" style="border-radius:12px;background:#cc4400">
                                            <a href="{{ $campaign->button_url }}" target="_blank" style="display:inline-block;padding:15px 34px;font-size:16px;font-weight:700;line-height:1;color:#ffffff;text-decoration:none;border-radius:12px;{{ $font }}">{{ $campaign->button_label }} &rarr;</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @else
                        <tr><td style="background:#ffffff;padding:0 0 24px"></td></tr>
                    @endif

                    {{-- The store's promises. --}}
                    <tr>
                        <td class="kn-pad" style="background:#f7f8fb;padding:22px 28px;border-top:1px solid #e3e8ef">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @foreach ([
                                        ['Livraison rapide', 'À Abidjan en 24 à 48 h'],
                                        ['Mobile Money', 'Orange, MTN, Moov, Wave'],
                                        ['Paiement à la livraison', 'Payez en recevant votre colis'],
                                    ] as [$title, $text])
                                        <td class="kn-col" width="33%" valign="top" style="padding:0 6px;{{ $font }}">
                                            <p style="margin:0 0 2px;font-size:13px;font-weight:700;color:#021732;{{ $font }}"><span style="color:#cc4400">&#9679;</span> {{ $title }}</p>
                                            <p style="margin:0;font-size:12px;line-height:1.45;color:#667085;{{ $font }}">{{ $text }}</p>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer: night blue, contacts, unsubscribe. --}}
                    <tr>
                        <td class="kn-pad" align="center" style="background:#021732;border-radius:0 0 16px 16px;border-top:3px solid #c69e3e;padding:28px 32px 30px;{{ $font }}">
                            <p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#ffffff;{{ $font }}">{{ $store }}</p>
                            <p style="margin:0 0 14px;font-size:13px;line-height:1.6;color:#b9c2d0;{{ $font }}">
                                {{ $contact['address'] ?? '' }}
                                @if (! empty($contact['phone']))<br>{{ $contact['phone'] }}@endif
                                @if (! empty($contact['email']))<br><a href="mailto:{{ $contact['email'] }}" style="color:#ffffff;text-decoration:underline">{{ $contact['email'] }}</a>@endif
                            </p>
                            <p style="margin:0 0 18px;font-size:13px;{{ $font }}">
                                <a href="{{ url('/boutique') }}" style="color:#ff8a4c;font-weight:600;text-decoration:none">La boutique</a>
                                <span style="color:#4b5b75">&nbsp;·&nbsp;</span>
                                <a href="{{ url('/suivi') }}" style="color:#ff8a4c;font-weight:600;text-decoration:none">Suivre ma commande</a>
                                @if ($whatsappUrl)
                                    <span style="color:#4b5b75">&nbsp;·&nbsp;</span>
                                    <a href="{{ $whatsappUrl }}" style="color:#ff8a4c;font-weight:600;text-decoration:none">WhatsApp</a>
                                @endif
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#8a96aa;{{ $font }}">
                                Vous recevez cet e-mail car vous êtes inscrit à la newsletter {{ $store }}.<br>
                                <a href="{{ $unsubscribeUrl }}" style="color:#ffffff;text-decoration:underline">Se désinscrire en un clic</a>
                                &nbsp;·&nbsp; © {{ now()->year }} {{ $store }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
