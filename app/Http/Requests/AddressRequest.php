<?php

namespace App\Http\Requests;

use App\Rules\IvorianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

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
            'district' => 'quartier',
            'landmark' => 'repère',
        ];
    }

    /**
     * @return array{label: string, recipient_name: string, phone: string, commune_id: int, district: string, landmark: ?string, is_default: bool}
     */
    public function details(): array
    {
        return [...$this->safe()->except('is_default'), 'is_default' => $this->boolean('is_default')];
    }
}
