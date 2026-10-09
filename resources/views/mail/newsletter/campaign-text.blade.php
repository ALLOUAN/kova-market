{{-- Plain-text copy of a newsletter campaign (mail apps without HTML, spam filters). --}}
@if ($isTest)
[E-MAIL DE TEST]

@endif
{{ $campaign->subject }}

{!! trim(html_entity_decode(strip_tags(preg_replace(['~<br\s*/?>~i', '~</(p|h2|h3|li|blockquote)>~i'], ["\n", "\n\n"], $campaign->content)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) !!}

@if ($campaign->button_label && $campaign->button_url)
{{ $campaign->button_label }} : {{ $campaign->button_url }}

@endif
--
{{ config('storefront.name') }} · {{ $contact['address'] ?? '' }}
Se désinscrire : {{ $unsubscribeUrl }}
