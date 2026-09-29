@props(['url'])
{{-- KOVA MARKET e-mails: the logo (PNG, read by every mail client) over a gold line. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('assets/images/logo/kova-logo.png') }}" class="logo" alt="{{ config('storefront.name', 'KOVA MARKET') }}" width="150" height="65">
</a>
</td>
</tr>
