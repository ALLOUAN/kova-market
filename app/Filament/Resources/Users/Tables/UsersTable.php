<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Role::from($state)->label()),
                IconColumn::make('app_authentication_secret')
                    ->label('Double authentification')
                    ->state(fn (User $record) => filled($record->app_authentication_secret))
                    ->boolean(),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetTwoFactor')
                    ->label('Réinitialiser la 2FA')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('À utiliser si la personne a perdu son téléphone. Elle devra reconfigurer une application d’authentification à sa prochaine connexion.')
                    ->visible(fn (User $record) => filled($record->app_authentication_secret))
                    ->action(function (User $record): void {
                        $record->saveAppAuthenticationSecret(null);
                        $record->saveAppAuthenticationRecoveryCodes(null);

                        activity()->causedBy(auth()->user())->performedOn($record)->log('Double authentification réinitialisée');

                        Notification::make()->title('Double authentification réinitialisée')->success()->send();
                    }),
                DeleteAction::make()
                    ->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }
}
