<?php

namespace App\Filament\Support;

use Filament\Widgets\Widget;

/**
 * Header band of a back-office list (filament.partials.list-hero): rendered with the page, full width, refreshed
 * every minute. Each list's widget gives its title, summary sentence and key figures.
 */
abstract class ListHeroWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.partials.list-hero';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{title: string, lead: string, icon: string, kpis: list<array<string, mixed>>}
     */
    abstract protected function hero(): array;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return $this->hero();
    }

    /**
     * One key figure card.
     *
     * @return array<string, mixed>
     */
    protected static function kpi(string $label, string $value, string $hint, string $icon, string $tone, ?string $url = null, ?string $accent = null, bool $money = false): array
    {
        return compact('label', 'value', 'hint', 'icon', 'tone', 'url', 'accent', 'money');
    }

    /**
     * "+12 %" badge against a previous value, empty when there is nothing to compare with.
     */
    protected static function trend(int $now, int $before): string
    {
        if ($before === 0) {
            return '';
        }

        $change = (int) round(($now - $before) / $before * 100);
        $class = $change > 0 ? 'is-up' : ($change < 0 ? 'is-down' : '');

        return ' <span class="kh-trend '.$class.'">'.($change > 0 ? '+' : ($change < 0 ? '−' : '')).abs($change).' %</span>';
    }
}
