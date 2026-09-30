<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a product is sold (Mon Marché): by the piece, by weight, by volume, by pack, by lot, or in a local unit named
 * on the product ("tas", "botte"…). Quantities are always stored as whole numbers of the base unit: grams for
 * "kg", millilitres for "litre", units otherwise, so stock, cart and orders stay integers.
 */
enum SaleUnit: string implements HasLabel
{
    case Piece = 'piece';
    case Kilogram = 'kg';
    case Litre = 'litre';
    case Pack = 'paquet';
    case Lot = 'lot';
    case Local = 'local';

    public function getLabel(): string
    {
        return match ($this) {
            self::Piece => 'À la pièce',
            self::Kilogram => 'Au poids (kg)',
            self::Litre => 'Au volume (litre)',
            self::Pack => 'Par paquet',
            self::Lot => 'Par lot',
            self::Local => 'Unité locale (tas, botte…)',
        };
    }

    /** Base units in one displayed unit: 1 kg = 1 000 g, 1 L = 1 000 mL. */
    public function factor(): int
    {
        return $this->isMeasured() ? 1000 : 1;
    }

    /** Weighed or measured: fractions of the unit can be bought (0,25 kg). */
    public function isMeasured(): bool
    {
        return $this === self::Kilogram || $this === self::Litre;
    }

    /** Short unit shown after a quantity or a price ("kg", "L"); null for pieces. */
    public function symbol(?string $localLabel = null): ?string
    {
        return match ($this) {
            self::Piece => null,
            self::Kilogram => 'kg',
            self::Litre => 'L',
            self::Pack => 'paquet',
            self::Lot => 'lot',
            self::Local => filled($localLabel) ? $localLabel : 'unité',
        };
    }

    /** Default step, in base units: 250 g, 500 mL, one unit. */
    public function defaultStep(): int
    {
        return match ($this) {
            self::Kilogram => 250,
            self::Litre => 500,
            default => 1,
        };
    }

    /** Default ceiling of one cart line, in base units: 50 kg, 50 L, 99 units. */
    public function defaultMax(): int
    {
        return $this->isMeasured() ? 50_000 : 99;
    }

    /**
     * SQL expression giving the factor of the product's unit, for thresholds compared in the database
     * (the column holding the unit is passed, e.g. "products.sale_unit").
     */
    public static function sqlFactor(string $column): string
    {
        return "(CASE WHEN {$column} IN ('".self::Kilogram->value."', '".self::Litre->value."') THEN 1000 ELSE 1 END)";
    }
}
