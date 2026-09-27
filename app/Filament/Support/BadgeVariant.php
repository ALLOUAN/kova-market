<?php

namespace App\Filament\Support;

/**
 * Badge colours understood by the storefront theme ("rbt-product-badge-bg-{variant}").
 */
class BadgeVariant
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'primary' => 'Principal',
            'secondary' => 'Secondaire',
            'secondary-gradient' => 'Dégradé',
            'green' => 'Vert',
            'yellow' => 'Jaune',
            'danger' => 'Rouge',
        ];
    }
}
