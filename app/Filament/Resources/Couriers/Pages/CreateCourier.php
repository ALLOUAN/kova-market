<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierResource;
use App\Services\Delivery\CourierAccounts;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;

    /**
     * The account (user with the "livreur" role) and its profile are created together; the SMS follows.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CourierAccounts::class)->create($data, auth()->user());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Livreur créé : ses identifiants lui ont été envoyés par SMS';
    }
}
