<?php

namespace App\Filament\Resources\Bundles\Pages;

use App\Filament\Resources\Bundles\BundleResource;
use App\Models\Product;
use App\Services\Catalog\StockManager;
use Filament\Resources\Pages\CreateRecord;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'is_bundle' => true, 'stock' => 0];
    }

    /**
     * The pack sells through its own variant (PACK-000123), whose stock follows the components.
     */
    protected function afterCreate(): void
    {
        /** @var Product $pack */
        $pack = $this->getRecord();
        $stock = app(StockManager::class);

        $stock->createDefaultVariant($pack, 'PACK-'.str_pad((string) $pack->getKey(), 6, '0', STR_PAD_LEFT), auth()->user());
        $stock->refreshPack($pack);
    }
}
