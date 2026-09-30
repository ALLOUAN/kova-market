<?php

namespace App\Support;

use App\Enums\SaleUnit;
use App\Models\OrderItem;
use App\Models\Product;

/**
 * The quantity rules of a product (or of a frozen order line): unit, minimum, step and ceiling, all in base units
 * (grams, millilitres or units). Everything that reads, checks, prices or shows a quantity goes through it:
 * "1 750" in the cart is shown "1,75 kg" and costs 1 000 × 1,75 at 1 000 FCFA/kg.
 */
final class SaleQuantity
{
    public function __construct(
        public readonly SaleUnit $unit,
        public readonly ?string $localLabel = null,
        private readonly ?int $minimum = null,
        private readonly ?int $step = null,
        private readonly ?int $maximum = null,
    ) {}

    public static function for(Product $product): self
    {
        return new self(
            $product->sale_unit ?? SaleUnit::Piece,
            $product->unit_label,
            $product->min_quantity,
            $product->quantity_step,
            $product->max_quantity,
        );
    }

    public static function forOrderItem(OrderItem $item): self
    {
        return new self($item->sale_unit ?? SaleUnit::Piece, $item->unit_label);
    }

    public function step(): int
    {
        return max(1, $this->step ?? $this->unit->defaultStep());
    }

    public function minimum(): int
    {
        return max($this->step(), $this->minimum ?? 0);
    }

    public function maximum(): int
    {
        return max($this->minimum(), $this->maximum ?? $this->unit->defaultMax());
    }

    /**
     * A quantity the product can be sold in: at least the minimum, a whole number of steps, at most the ceiling
     * and the stock (null when there is no stock limit). 0 when not even the minimum is in stock.
     */
    public function normalize(int $quantity, ?int $stock = null): int
    {
        $ceiling = min($this->maximum(), $stock ?? PHP_INT_MAX);
        $ceiling -= $ceiling % $this->step();

        if ($ceiling < $this->minimum()) {
            return 0;
        }

        $quantity = max($this->minimum(), $quantity - $quantity % $this->step());

        return min($quantity, $ceiling);
    }

    /** Price of a quantity at a price per displayed unit, in whole FCFA (rounded to the nearest). */
    public function lineTotal(int $unitPrice, int $quantity): int
    {
        return (int) round($unitPrice * $quantity / $this->unit->factor());
    }

    /** The quantity in displayed units (1 750 → 1.75). */
    public function toDisplay(int $quantity): float|int
    {
        return $this->unit->isMeasured() ? $quantity / $this->unit->factor() : $quantity;
    }

    /** "1,75 kg", "0,5 L", "3 tas", "2 paquets", "2" for pieces. */
    public function format(int $quantity): string
    {
        $number = $this->number($this->toDisplay($quantity));

        return match ($this->unit) {
            SaleUnit::Piece => $number,
            SaleUnit::Pack, SaleUnit::Lot => $number.' '.$this->unit->symbol().($quantity > 1 ? 's' : ''),
            default => $number.' '.$this->unit->symbol($this->localLabel),
        };
    }

    /** What follows a price: " / kg", " / tas"; empty for pieces. */
    public function priceSuffix(): string
    {
        $symbol = $this->unit->symbol($this->localLabel);

        return $symbol === null ? '' : ' / '.$symbol;
    }

    /** "1,75 kg × 1 000 FCFA/kg" or "2 × 4 500 FCFA": how a line was priced. */
    public function describe(int $quantity, int $unitPrice): string
    {
        return $this->unit === SaleUnit::Piece
            ? $quantity.' × '.Money::format($unitPrice)
            : $this->format($quantity).' × '.Money::format($unitPrice).str_replace(' / ', '/', $this->priceSuffix());
    }

    /** Up to two decimals, French style, no trailing zeros: 1,75 · 0,5 · 2. */
    private function number(float|int $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}
