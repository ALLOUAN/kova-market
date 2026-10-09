<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\ListHeroWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Header of the team (back-office accounts): active members by role, protected by an authenticator app, suspended.
 */
class UsersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $team = fn (): Builder => UserResource::getEloquentQuery();
        $active = $team()->whereNull('suspended_at')->count();
        $suspended = $team()->whereNotNull('suspended_at')->count();
        $protected = $team()->whereNull('suspended_at')->whereNotNull('app_authentication_secret')->count();
        $byRole = fn (Role $role) => $team()->whereNull('suspended_at')->role($role->value)->count();
        $tab = fn (string $tab) => UserResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Équipe',
            'icon' => 'heroicon-o-user-group',
            'lead' => '<strong>'.$active.'</strong> membre'.($active > 1 ? 's' : '').' de l’équipe ont accès à l’administration'
                .($active - $protected > 0 ? ', dont <strong>'.($active - $protected).'</strong> sans double authentification.' : ', tous protégés par la double authentification.'),
            'kpis' => [
                self::kpi('Membres actifs', (string) $active, $byRole(Role::SuperAdmin).' administrateur(s) · '.$byRole(Role::Manager).' gestionnaire(s) · '.$byRole(Role::Picker).' préparateur(s)',
                    'heroicon-o-user-group', 'navy', $tab('active')),
                self::kpi('Double authentification', $active > 0 ? round($protected / $active * 100).' %' : '—', $protected.' compte(s) protégé(s) par une application',
                    'heroicon-o-shield-check', $protected === $active ? 'green' : 'gold', null, $protected < $active ? 'gold' : null),
                self::kpi('Suspendus', (string) $suspended, 'Ne peuvent plus se connecter',
                    'heroicon-o-no-symbol', 'navy', $tab('suspended')),
            ],
        ];
    }
}
