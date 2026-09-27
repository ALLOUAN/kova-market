<?php

namespace App\Filament\Resources\DeliveryZones\Pages;

use App\Filament\Resources\DeliveryZones\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryZone extends EditRecord
{
    protected static string $resource = DeliveryZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Communes would be left without zone: move or remove them first.
            DeleteAction::make()->hidden(fn (DeliveryZone $record) => $record->communes()->exists()),
        ];
    }
}
