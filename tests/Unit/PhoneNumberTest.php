<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function sameNumberTypedDifferently(): array
    {
        return [
            'local' => ['0701020304'],
            'local with spaces' => ['07 01 02 03 04'],
            'local with dots' => ['07.01.02.03.04'],
            'international' => ['+2250701020304'],
            'international with spaces' => ['+225 07 01 02 03 04'],
            'without plus' => ['2250701020304'],
            'with 00' => ['00225 07 01 02 03 04'],
        ];
    }

    #[DataProvider('sameNumberTypedDifferently')]
    public function test_every_usual_format_is_stored_the_same_way(string $typed): void
    {
        $this->assertSame('+2250701020304', PhoneNumber::normalize($typed));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'too short' => ['07010203'],
            'too long' => ['070102030405'],
            'old 8-digit format' => ['01020304'],
            'not starting with 0' => ['7701020304'],
            'other country' => ['+33612345678'],
            'letters' => ['07 01 AB 03 04'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected(?string $typed): void
    {
        $this->assertNull(PhoneNumber::normalize($typed));
        $this->assertFalse(PhoneNumber::isValid($typed));
    }

    public function test_numbers_are_displayed_by_pairs(): void
    {
        $this->assertSame('+225 07 01 02 03 04', PhoneNumber::format('+2250701020304'));
    }
}
