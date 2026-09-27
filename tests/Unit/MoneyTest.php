<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_are_whole_fcfa_with_grouped_thousands(): void
    {
        $this->assertSame("15\u{00A0}000\u{00A0}FCFA", Money::format(15000));
        $this->assertSame("1\u{00A0}250\u{00A0}000\u{00A0}FCFA", Money::format('1250000.00'));
        $this->assertSame("0\u{00A0}FCFA", Money::format(null));
    }

    public function test_decimals_are_rounded_away(): void
    {
        $this->assertSame("108\u{00A0}000\u{00A0}FCFA", Money::format(107999.6));
    }
}
