<?php

namespace App\Support;

/**
 * Formats amounts in the store currency: whole FCFA, no decimals, e.g. "15 000 FCFA".
 */
class Money
{
    /** Non-breaking space: keeps "15 000 FCFA" on one line. */
    private const SPACE = "\u{00A0}";

    public static function format(int|float|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', self::SPACE).self::SPACE.config('storefront.currency_symbol');
    }
}
