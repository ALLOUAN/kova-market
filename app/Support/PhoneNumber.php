<?php

namespace App\Support;

/**
 * Ivorian phone numbers: 10 digits starting with 0, stored in international form "+225XXXXXXXXXX"
 * (specification F-051). Spaces, dots, dashes and the 225 / +225 / 00225 prefixes are accepted as typed.
 */
class PhoneNumber
{
    public const COUNTRY_CODE = '225';

    /**
     * International form, or null when the input is not a valid Ivorian number.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $input);

        if (str_starts_with($digits, '00'.self::COUNTRY_CODE)) {
            $digits = substr($digits, 2 + strlen(self::COUNTRY_CODE));
        } elseif (strlen($digits) === 13 && str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, strlen(self::COUNTRY_CODE));
        }

        return preg_match('/^0\d{9}$/', $digits) === 1 ? '+'.self::COUNTRY_CODE.$digits : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    /**
     * Human-readable form, e.g. "+225 07 01 02 03 04".
     */
    public static function format(?string $phone): string
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return (string) $phone;
        }

        return '+'.self::COUNTRY_CODE.' '.implode(' ', str_split(substr($normalized, 4), 2));
    }
}
