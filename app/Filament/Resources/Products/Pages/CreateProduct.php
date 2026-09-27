<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\StockManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * The price, compare price and opening stock typed on creation become the product's default variant.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create(Arr::except($data, 'sku'));

            app(StockManager::class)->createDefaultVariant($product, $data['sku'] ?? null, auth()->user());

            return $product;
        });
    }
}
