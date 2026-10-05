<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'commune' => CommuneResource::make($this->whenLoaded('commune')),
            'commune_id' => $this->commune_id,
            'city' => $this->city,
            'district' => $this->district,
            'landmark' => $this->landmark,
            'is_default' => $this->is_default,
        ];
    }
}
