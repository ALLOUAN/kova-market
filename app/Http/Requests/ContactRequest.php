<?php

namespace App\Http\Requests;

use App\Enums\ContactSubject;
use App\Rules\IvorianPhoneNumber;
use App\Services\Security\Turnstile;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Contact form (F-080) with its anti-robot checks: a hidden field people never fill, a minimum time between
 * the page display and the sending (robots post at once), Turnstile when configured, and a throttle on the route.
 */
class ContactRequest extends FormRequest
{
    /** Seconds a person needs at least to fill in the form. */
    public const MINIMUM_SECONDS = 3;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new IvorianPhoneNumber],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', Rule::enum(ContactSubject::class)],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nom', 'phone' => 'téléphone', 'subject' => 'sujet'];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->looksHuman()) {
                $validator->errors()->add('message', 'Votre message n’a pas pu être envoyé. Merci de réessayer dans un instant.');
            }
        }];
    }

    /**
     * Details to store, phone in its stored form.
     *
     * @return array{name: string, phone: string, email: ?string, subject: string, message: string}
     */
    public function details(): array
    {
        return [
            ...$this->safe()->only(['name', 'email', 'subject', 'message']),
            'phone' => PhoneNumber::normalize($this->validated('phone')),
        ];
    }

    private function looksHuman(): bool
    {
        if (filled($this->input('website'))) {
            return false;
        }

        try {
            $shownAt = (int) Crypt::decryptString((string) $this->input('started_at'));
        } catch (DecryptException) {
            return false;
        }

        $elapsed = now()->timestamp - $shownAt;

        return $elapsed >= self::MINIMUM_SECONDS
            && $elapsed <= 2 * 3600
            && app(Turnstile::class)->passes($this->input('cf-turnstile-response'), $this->ip());
    }
}
