<?php

namespace Tests\Unit;

use App\Enums\SaleUnit;
use App\Support\SaleQuantity;
use Tests\TestCase;

/**
 * Quantity rules of the sale units: stored in base units, priced per displayed unit, shown the French way.
 */
class SaleQuantityTest extends TestCase
{
    public function test_a_product_sold_by_the_kilo_is_priced_on_the_weight(): void
    {
        $kilo = new SaleQuantity(SaleUnit::Kilogram);

        $this->assertSame([500, 1500, 2750], [$kilo->lineTotal(1000, 500), $kilo->lineTotal(1000, 1500), $kilo->lineTotal(1000, 2750)]);
        // Whole FCFA, rounded to the nearest: 0,333 kg at 750 FCFA/kg.
        $this->assertSame(250, $kilo->lineTotal(750, 333));

        $this->assertSame(['0,25 kg', '1,75 kg', '2 kg', '0,5 kg'], [$kilo->format(250), $kilo->format(1750), $kilo->format(2000), $kilo->format(500)]);
        $this->assertSame(' / kg', $kilo->priceSuffix());
        $this->assertSame("1,75 kg × 1\u{00A0}000\u{00A0}FCFA/kg", $kilo->describe(1750, 1000));
    }

    public function test_quantities_follow_the_minimum_the_step_the_ceiling_and_the_stock(): void
    {
        // Defaults for the kilo: 250 g steps, from 250 g, up to 50 kg.
        $kilo = new SaleQuantity(SaleUnit::Kilogram);
        $this->assertSame([250, 250, 50_000], [$kilo->step(), $kilo->minimum(), $kilo->maximum()]);
        $this->assertSame(250, $kilo->normalize(1));
        $this->assertSame(1750, $kilo->normalize(1800));
        $this->assertSame(50_000, $kilo->normalize(80_000));
        $this->assertSame(1750, $kilo->normalize(5000, stock: 1900));
        $this->assertSame(0, $kilo->normalize(500, stock: 200));

        // Set on the product: from 1 kg, by 500 g.
        $bulk = new SaleQuantity(SaleUnit::Kilogram, minimum: 1000, step: 500);
        $this->assertSame([1000, 1500], [$bulk->normalize(250), $bulk->normalize(1700)]);
    }

    public function test_pieces_packs_and_local_units(): void
    {
        $piece = new SaleQuantity(SaleUnit::Piece);
        $this->assertSame(['3', '', 99], [$piece->format(3), $piece->priceSuffix(), $piece->normalize(150)]);
        $this->assertSame(9000, $piece->lineTotal(4500, 2));
        $this->assertSame("2 × 4\u{00A0}500\u{00A0}FCFA", $piece->describe(2, 4500));

        $this->assertSame(['1 paquet', '3 paquets'], [(new SaleQuantity(SaleUnit::Pack))->format(1), (new SaleQuantity(SaleUnit::Pack))->format(3)]);
        $this->assertSame(['3 tas', ' / tas'], [(new SaleQuantity(SaleUnit::Local, 'tas'))->format(3), (new SaleQuantity(SaleUnit::Local, 'tas'))->priceSuffix()]);
        $this->assertSame('1,5 L', (new SaleQuantity(SaleUnit::Litre))->format(1500));
    }
}
