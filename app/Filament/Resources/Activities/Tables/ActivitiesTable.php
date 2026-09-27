<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Enums\Role;
use App\Filament\Resources\Activities\ActivityResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    private const EVENTS = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['causer', 'subject']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('causer.name')->label('Auteur')->placeholder('Système'),
                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Activity $record) => self::EVENTS[$state] ?? $record->description),
                TextColumn::make('subject_type')
                    ->label('Élément')
                    ->formatStateUsing(fn (?string $state) => ActivityResource::subjectLabel($state))
                    ->description(fn (Activity $record) => $record->subject?->name ?? $record->subject?->title ?? $record->subject?->question ?? ($record->subject_id ? "#{$record->subject_id}" : null)),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->label('Élément')
                    ->options(ActivityResource::SUBJECT_LABELS),
                SelectFilter::make('event')
                    ->label('Action')
                    ->options(self::EVENTS),
                // "causer" is polymorphic: list the staff members explicitly.
                SelectFilter::make('causer_id')
                    ->label('Auteur')
                    ->options(fn () => User::role(array_map(fn (Role $role) => $role->value, Role::panelRoles()))->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $userId) => $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', $userId),
                    )),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Détail')
                    ->schema([
                        TextEntry::make('description')->label('Description'),
                        KeyValueEntry::make('old')
                            ->label('Avant')
                            ->keyLabel('Champ')
                            ->valueLabel('Valeur')
                            ->state(fn (Activity $record) => self::printable($record->attribute_changes?->get('old'))),
                        KeyValueEntry::make('new')
                            ->label('Après')
                            ->keyLabel('Champ')
                            ->valueLabel('Valeur')
                            ->state(fn (Activity $record) => self::printable($record->attribute_changes?->get('attributes'))),
                    ]),
            ]);
    }

    /**
     * Flattens logged values (arrays, booleans, nulls) into displayable strings.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, string>
     */
    private static function printable(?array $values): array
    {
        return collect($values ?? [])
            ->map(fn ($value) => match (true) {
                is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                is_bool($value) => $value ? 'oui' : 'non',
                $value === null => '—',
                default => (string) $value,
            })
            ->all();
    }
}
