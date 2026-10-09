<?php

namespace App\Filament\Resources\Activities\Widgets;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Support\ListHeroWidget;
use Spatie\Activitylog\Models\Activity;

/**
 * Header of the audit log: actions today and this week, who acted, and the latest action.
 */
class ActivitiesOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $today = Activity::query()->where('created_at', '>=', today())->count();
        $week = Activity::query()->where('created_at', '>=', now()->subDays(7))->count();
        $authors = Activity::query()->where('created_at', '>=', now()->subDays(7))->whereNotNull('causer_id')->distinct()->count('causer_id');
        $system = Activity::query()->where('created_at', '>=', now()->subDays(7))->whereNull('causer_id')->count();
        $last = Activity::query()->latest()->with('causer')->first();
        $tab = fn (string $tab) => ActivityResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Journal d’audit',
            'icon' => 'heroicon-o-clipboard-document-list',
            'lead' => 'Qui a changé quoi, et quand, dans l’administration : <strong>'.$today.'</strong> action'.($today > 1 ? 's' : '').' aujourd’hui. Le journal ne se modifie pas.',
            'kpis' => [
                self::kpi('Aujourd’hui', (string) $today, 'Actions enregistrées',
                    'heroicon-o-calendar', 'orange', $tab('today')),
                self::kpi('7 derniers jours', (string) $week, $system.' faite(s) par le système',
                    'heroicon-o-calendar-days', 'navy', $tab('week')),
                self::kpi('Auteurs', (string) $authors, 'Personnes ayant agi cette semaine',
                    'heroicon-o-user-group', 'green'),
                self::kpi('Dernière action', $last ? $last->created_at->format('H:i') : '—', $last ? e(($last->causer?->name ?? 'Système').' · '.$last->description) : 'Journal vide',
                    'heroicon-o-clock', 'gold'),
            ],
        ];
    }
}
