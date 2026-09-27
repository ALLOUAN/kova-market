<?php

namespace App\Filament\Resources\Bundles\Pages;

use App\Filament\Resources\Bundles\BundleResource;
use App\Models\Product;
use App\Services\Catalog\StockManager;
use Filament\Resources\Pages\EditRecord;

class EditBundle extends EditRecord
{
    protected static string $resource = BundleResource::class;

    /**
     * Prices are edited on the pack's variant; the product only keeps their current summary.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $variant = $this->getRecord()->variants()->first();

        return [
            ...$data,
            'price' => $variant?->price ?? $data['price'],
            'compare_at_price' => $variant?->compare_at_price,
            'sale_ends_at' => $variant?->sale_ends_at,
        ];
    }

    protected function afterSave(): void
    {
        /** @var Product $pack */
        $pack = $this->getRecord();
        $data = $this->form->getState();

        $pack->variants()->first()?->update([
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'sale_ends_at' => $data['sale_ends_at'] ?? null,
        ]);

        app(StockManager::class)->refreshPack($pack->refresh());
    }
}
