<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierActions;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Couriers\Widgets\CourierFinanceHistory;
use App\Filament\Resources\Couriers\Widgets\CourierFinanceOverview;
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

    /**
     * F-126: what the courier delivered, collected and handed over, and the latest movements.
     */
    protected function getHeaderWidgets(): array
    {
        return [CourierFinanceOverview::class];
    }

    protected function getFooterWidgets(): array
    {
        return [CourierFinanceHistory::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            CourierActions::settleCash(),
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
