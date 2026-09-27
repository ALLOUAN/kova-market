<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Banner slots of the home page template. Only the hero is a slider; the others show one banner.
 */
enum BannerPlacement: string implements HasLabel
{
    case Hero = 'hero';
    case Categories = 'categories';
    case BestDeals = 'best_deals';
    case Highlights = 'highlights';
    case Closing = 'closing';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Carrousel principal',
            self::Categories => 'À côté des catégories populaires',
            self::BestDeals => 'Au-dessus des meilleures offres',
            self::Highlights => 'À côté des incontournables',
            self::Closing => 'Bannière de fin de page',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function isSlider(): bool
    {
        return $this === self::Hero;
    }

    /**
     * Slots that display a price (the others ignore it).
     */
    public function showsPrice(): bool
    {
        return in_array($this, [self::Hero, self::Closing], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $placement) => [$placement->value => $placement->label()])->all();
    }
}
