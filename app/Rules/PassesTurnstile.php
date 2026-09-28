<?php

namespace App\Rules;

use App\Services\Security\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Anti-robot check of the public storefront forms (F-141): the Turnstile token of the "cf-turnstile-response"
 * field, once the keys are set. API clients (the mobile app cannot show the widget) rely on the rate limits.
 */
class PassesTurnstile implements ValidationRule
{
    /** Checked even when the field is missing: a robot simply leaves it out. */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (request()->is('api/*')) {
            return;
        }

        if (! app(Turnstile::class)->passes(is_string($value) ? $value : null, request()->ip())) {
            $fail('Merci de confirmer que vous n’êtes pas un robot.');
        }
    }
}
