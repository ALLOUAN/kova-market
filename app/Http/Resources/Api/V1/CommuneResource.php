<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Commune;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Commune
 */
class CommuneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'deliverable' => $this->isDeliverable(),
            'zone' => $this->zone ? [
                'name' => $this->zone->name,
                'fee' => $this->zone->fee,
                'delay' => $this->zone->delay_label,
            ] : null,
        ];
    }
}
