<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compte')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nom complet')->required()->maxLength(255),
                        TextInput::make('email')->label('E-mail de connexion')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                        Select::make('roles')
                            ->label('Rôle')
                            ->relationship(
                                'roles',
                                'name',
                                // Courier accounts are created with the delivery module (zones, vehicle, phone login).
                                fn (Builder $query) => $query->whereIn('name', array_map(fn (Role $role) => $role->value, Role::panelRoles())),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => Role::from($record->name)->label())
                            ->multiple()
                            ->maxItems(1)
                            ->preload()
                            ->required()
                            // A super-admin cannot take away their own access.
                            ->disabled(fn (?User $record) => $record?->is(auth()->user()) ?? false),
                        TextInput::make('password')
                            ->label(fn (string $operation) => $operation === 'create' ? 'Mot de passe' : 'Nouveau mot de passe')
                            ->helperText(fn (string $operation) => $operation === 'create' ? '12 caractères minimum.' : 'Laisser vide pour ne pas le changer.')
                            ->password()
                            ->revealable()
                            ->minLength(12)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state)),
                    ]),
            ]);
    }
}
