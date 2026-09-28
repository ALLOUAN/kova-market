<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Rules\IvorianPhoneNumber;
use App\Rules\PassesTurnstile;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Order form (F-050, F-051, F-053): identity, Ivorian phone number, optional e-mail, commune, district,
 * landmark, note, payment method, mandatory consent and separate marketing opt-in.
 */
class PlaceOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new IvorianPhoneNumber],
            'email' => ['nullable', 'email', 'max:255'],
            'commune_id' => ['required', 'integer', 'exists:communes,id'],
            'district' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'terms' => ['accepted'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'cf-turnstile-response' => [new PassesTurnstile],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nom complet',
            'phone' => 'téléphone',
            'commune_id' => 'commune',
            'district' => 'quartier',
            'landmark' => 'repère',
            'payment_method' => 'mode de paiement',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Merci d’accepter les conditions générales de vente et la politique de confidentialité.',
        ];
    }

    /**
     * Validated details, phone in its stored form.
     *
     * @return array{customer_name: string, phone: string, email: ?string, commune_id: int, district: string, landmark: ?string, note: ?string, payment_method: string, marketing_opt_in: bool}
     */
    public function details(): array
    {
        return [
            ...$this->safe()->except(['terms', 'marketing_opt_in', 'cf-turnstile-response']),
            'phone' => PhoneNumber::normalize($this->validated('phone')),
            'commune_id' => (int) $this->validated('commune_id'),
            'marketing_opt_in' => $this->boolean('marketing_opt_in'),
        ];
    }
}
