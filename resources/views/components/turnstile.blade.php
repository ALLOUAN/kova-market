@inject('turnstile', 'App\Services\Security\Turnstile')

{{-- Anti-robot widget of the public forms (F-141), shown once the Turnstile keys are set; the layout loads its script. --}}
@if ($turnstile->enabled())
    <div {{ $attributes->class(['cf-turnstile']) }} data-sitekey="{{ $turnstile->siteKey() }}" data-language="fr"></div>
    @error('cf-turnstile-response')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
@endif
