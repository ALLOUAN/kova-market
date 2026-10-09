{{-- Newsletter sign-up form (footer, invitation window). $source: where the address is given. --}}
@php($status = session('newsletter_status'))
<form method="POST" action="{{ route('newsletter.store') }}" class="{{ $formClass ?? '' }} kova-newsletter-form" data-newsletter-form novalidate>
    @csrf
    <input type="hidden" name="source" value="{{ $source }}">
    {{-- Left empty by people, filled by robots. --}}
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="kova-hp">
    <label for="newsletter-email-{{ $source }}" class="visually-hidden">Adresse e-mail</label>
    <input id="newsletter-email-{{ $source }}" type="email" name="email" placeholder="Votre adresse e-mail" autocomplete="email" required>
    <button type="submit" class="rbt-btn rbt-btn-md">S’abonner</button>
    @if (($formClass ?? '') !== '' && str_contains($formClass, 'rbt-newsletter-form-one'))
        <div class="icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></div>
    @endif
</form>
{{-- Answers open in the message window (assets/js/flash.js); this line only serves when that window cannot open. --}}
@if ($status)
    <div class="alert alert-success kova-newsletter-alert" data-popup role="status">{{ $status }}</div>
@endif
<p class="kova-newsletter-message" data-newsletter-message role="status" aria-live="polite"></p>
<p class="kova-newsletter-note">Quelques e-mails par mois au plus. Désinscription en un clic.</p>

@once
    @push('scripts')
        <script>
            // Newsletter forms answer in the message window without reloading; without JavaScript the plain POST
            // redirects back with the same message (shown in the window on load).
            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-newsletter-form]');
                if (!form) { return; }
                event.preventDefault();
                var message = form.parentElement.querySelector('[data-newsletter-message]');
                var button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
                    .then(function (result) {
                        var text = result.data.message || '';
                        if (!result.ok && result.data.errors) { text = Object.values(result.data.errors)[0][0]; }
                        var shown = window.kovaFlash && window.kovaFlash(text, result.ok ? 'success' : 'danger');
                        message.textContent = shown ? '' : text;
                        message.classList.toggle('is-error', !result.ok);
                        if (result.ok) {
                            form.reset();
                            if (window.kovaTrack) { window.kovaTrack(result.data.analytics); }
                            try { localStorage.setItem('kova_newsletter', '1'); } catch (e) {}
                        }
                    })
                    .catch(function () {
                        var text = 'L’inscription n’a pas pu aboutir. Réessayez dans un instant.';
                        var shown = window.kovaFlash && window.kovaFlash(text, 'danger');
                        message.textContent = shown ? '' : text;
                        message.classList.add('is-error');
                    })
                    .finally(function () { button.disabled = false; });
            });
        </script>
    @endpush
@endonce
