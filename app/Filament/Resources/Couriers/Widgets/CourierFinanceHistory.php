<?php

namespace App\Filament\Resources\Couriers\Widgets;

use App\Enums\Permission;
use App\Models\Courier;
use App\Services\Delivery\CourierFinances;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * The courier's latest money movements (F-126), newest first: cash collected at each delivery and payments to the
 * store, cancelled payments included and marked.
 */
class CourierFinanceHistory extends Widget
{
    protected string $view = 'filament.couriers.finance-history';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewFinances->value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Courier $courier */
        $courier = $this->record;

        return ['entries' => app(CourierFinances::class)->history($courier)];
    }
}
