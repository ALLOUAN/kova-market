<?php

namespace App\Services\Finance;

use Carbon\CarbonImmutable;

/**
 * A period of the finance dashboard: today, this week, this month, this year or chosen dates (Africa/Abidjan,
 * whole days), and the period of the same length just before it, to compare.
 */
final readonly class FinancePeriod
{
    public const PRESETS = [
        'today' => 'Aujourd’hui',
        'week' => 'Cette semaine',
        'month' => 'Ce mois',
        'year' => 'Cette année',
        'custom' => 'Dates choisies',
    ];

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $label,
    ) {}

    public static function make(string $preset, ?string $from = null, ?string $to = null): self
    {
        $now = CarbonImmutable::now();

        return match ($preset) {
            'today' => new self($now->startOfDay(), $now->endOfDay(), 'Aujourd’hui'),
            'week' => new self($now->startOfWeek(), $now->endOfWeek(), 'Cette semaine'),
            'year' => new self($now->startOfYear(), $now->endOfYear(), 'Année '.$now->year),
            'custom' => self::custom($from, $to),
            default => new self($now->startOfMonth(), $now->endOfMonth(), ucfirst($now->translatedFormat('F Y'))),
        };
    }

    private static function custom(?string $from, ?string $to): self
    {
        $start = $from ? CarbonImmutable::parse($from)->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $end = $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return new self($start, $end, 'Du '.$start->format('d/m/Y').' au '.$end->format('d/m/Y'));
    }

    /**
     * Same number of days, right before.
     */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to->addSecond());
        $from = $this->from->subDays($days);

        return new self($from, $this->from->subSecond(), 'période précédente');
    }

    /**
     * Bucket of the revenue curve: days up to two months, weeks up to a semester, months beyond.
     */
    public function bucket(): string
    {
        $days = $this->from->diffInDays($this->to);

        return match (true) {
            $days <= 62 => 'day',
            $days <= 186 => 'week',
            default => 'month',
        };
    }
}
