<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierActions;
use App\Filament\Resources\Couriers\CourierResource;
use App\Models\Courier;
use App\Services\Delivery\CourierAccounts;
use App\Support\PhoneNumber;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->name();
    }

    protected function getHeaderActions(): array
    {
        return [
            CourierActions::resetPassword(),
            CourierActions::suspend(),
            CourierActions::reactivate(),
        ];
    }

    /**
     * Name and phone live on the account.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Courier $courier */
        $courier = $this->getRecord();

        return [
            ...$data,
            'name' => $courier->user->name,
            'phone' => PhoneNumber::format($courier->user->phone),
            'zones' => $courier->zones->modelKeys(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CourierAccounts::class)->update($record, $data);
    }
}
