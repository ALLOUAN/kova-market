<?php

namespace App\Filament\Support;

use App\Enums\SaleUnit;
use Closure;
use Filament\Forms\Components\TextInput;

/**
 * A quantity typed in the product's displayed unit and stored in base units: "1,5" kg is saved as 1 500 g,
 * "2" pieces as 2. The unit comes from a closure evaluated by Filament (it may read the form: fn (Get $get) => …).
 */
class QuantityInput
{
    /**
     * @param  Closure(): (SaleUnit|string|null)  $unit
     */
    public static function make(string $name, string $label, Closure $unit): TextInput
    {
        $resolve = fn (TextInput $component): SaleUnit => self::unit($component->evaluate($unit));

        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->step(fn (TextInput $component) => $resolve($component)->isMeasured() ? 0.01 : 1)
            ->suffix(fn (TextInput $component) => $resolve($component)->symbol() ?? 'unité(s)')
            ->formatStateUsing(fn (TextInput $component, $state) => $state === null || $state === ''
                ? null
                : (float) $state / $resolve($component)->factor())
            ->dehydrateStateUsing(fn (TextInput $component, $state) => $state === null || $state === ''
                ? null
                : (int) round((float) str_replace(',', '.', (string) $state) * $resolve($component)->factor()));
    }

    /** The unit from a form value: an enum, its value, or nothing (by the piece). */
    public static function unit(SaleUnit|string|null $unit): SaleUnit
    {
        return $unit instanceof SaleUnit ? $unit : (SaleUnit::tryFrom((string) $unit) ?? SaleUnit::Piece);
    }
}
