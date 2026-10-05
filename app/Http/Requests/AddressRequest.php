<?php

namespace App\Http\Requests;

use App\Models\Commune;
use App\Rules\IvorianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An address of the customer's address book (F-071), from the storefront and the API.
 */
class AddressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new IvorianPhoneNumber],
            'commune_id' => ['required', 'integer', 'exists:communes,id'],
            // "Intérieur": the town the parcel is shipped to.
            'city' => [Rule::requiredIf(fn () => (bool) Commune::with('zone')->find((int) $this->input('commune_id'))?->isInterior()), 'nullable', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label' => 'nom de l’adresse',
            'recipient_name' => 'destinataire',
            'phone' => 'téléphone',
            'commune_id' => 'commune',
            'city' => 'ville',
            'district' => 'quartier',
            'landmark' => 'repère',
        ];
    }

    /**
     * @return array{label: string, recipient_name: string, phone: string, commune_id: int, city: ?string, district: string, landmark: ?string, is_default: bool}
     */
    public function details(): array
    {
        $interior = (bool) Commune::with('zone')->find((int) $this->validated('commune_id'))?->isInterior();

        return [
            ...$this->safe()->except(['is_default', 'city']),
            // Only an address in the interior keeps a town.
            'city' => $interior ? trim((string) $this->validated('city')) : null,
            'is_default' => $this->boolean('is_default'),
        ];
    }
}
