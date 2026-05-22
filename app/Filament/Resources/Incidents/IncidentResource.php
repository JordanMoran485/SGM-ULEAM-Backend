<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Resources\Incidents\Pages\ManageIncidents;
use App\Models\Incident;
use App\Models\Task;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $modelLabel = 'Incidencia';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titulo')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(4)
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->label('Ubicacion')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Evidencia')
                    ->image()
                    ->disk('public')
                    ->directory('incidents')
                    ->imageEditor()
                    ->visibility('public')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ])
                    ->required(),
                Select::make('review_status')
                    ->label('Revision')
                    ->options([
                        'Pendiente de revision' => 'Pendiente de revision',
                        'Aceptada' => 'Aceptada',
                        'Rechazada' => 'Rechazada',
                    ])
                    ->disabled(),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'Baja' => 'Baja',
                        'Media' => 'Media',
                        'Alta' => 'Alta',
                    ])
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image')
                    ->label('Evidencia')
                    ->disk('public')
                    ->visibility('public')
                    ->height(320)
                    ->columnSpanFull(),
                TextEntry::make('title')
                    ->label('Titulo'),
                TextEntry::make('description')
                    ->label('Descripcion')
                    ->columnSpanFull(),
                TextEntry::make('user.name')
                    ->label('Reportado por')
                    ->formatStateUsing(fn (?string $state, Incident $record): string => trim(($record->user?->name ?? '') . ' ' . ($record->user?->lastname ?? '')) ?: '-'),
                TextEntry::make('location')
                    ->label('Ubicacion')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    }),
                TextEntry::make('review_status')
                    ->label('Revision')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente de revision' => 'gray',
                        'Aceptada' => 'success',
                        'Rechazada' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    }),
                TextEntry::make('tasks_count')
                    ->label('Tareas asociadas'),
                TextEntry::make('reviewer.name')
                    ->label('Revisado por')
                    ->formatStateUsing(fn (?string $state, Incident $record): string => trim(($record->reviewer?->name ?? '') . ' ' . ($record->reviewer?->lastname ?? '')) ?: '-'),
                TextEntry::make('reviewed_at')
                    ->label('Fecha de revision')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('review_notes')
                    ->label('Observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(52),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Reportado por')
                    ->formatStateUsing(fn (?string $state, Incident $record): string => trim(($record->user?->name ?? '') . ' ' . ($record->user?->lastname ?? '')) ?: '-')
                    ->searchable(['users.name', 'users.lastname']),
                TextColumn::make('location')
                    ->label('Ubicacion')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('review_status')
                    ->label('Revision')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente de revision' => 'gray',
                        'Aceptada' => 'success',
                        'Rechazada' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('tasks_count')
                    ->label('Tareas')
                    ->counts('tasks'),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'Alta' => 'Alta',
                        'Media' => 'Media',
                        'Baja' => 'Baja',
                    ]),
                SelectFilter::make('review_status')
                    ->label('Revision')
                    ->options([
                        'Pendiente de revision' => 'Pendiente de revision',
                        'Aceptada' => 'Aceptada',
                        'Rechazada' => 'Rechazada',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Aprobar y asignar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Incident $record): bool => $record->review_status !== 'Aceptada')
                    ->form([
                        Select::make('user_id')
                            ->label('Conserje encargado')
                            ->options(fn (): array => User::conserjeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('all_day')
                            ->label('Todo el dia')
                            ->default(true)
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label('Inicio')
                            ->native(false)
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('end_at')
                            ->label('Fin')
                            ->native(false)
                            ->seconds(false),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'Baja' => 'Baja',
                                'Media' => 'Media',
                                'Alta' => 'Alta',
                            ])
                            ->default(fn (Incident $record): string => $record->priority)
                            ->required(),
                        Textarea::make('review_notes')
                            ->label('Observaciones para el conserje')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Incident $record, array $data): void {
                        $task = Task::create([
                            'incident_id' => $record->getKey(),
                            'title' => $record->title,
                            'description' => $data['review_notes'] ?: $record->description,
                            'user_id' => $data['user_id'],
                            'image' => $record->image,
                            'status' => 'Pendiente',
                            'priority' => $data['priority'],
                            'location' => $record->location,
                            'all_day' => $data['all_day'],
                            'start_at' => $data['start_at'],
                            'end_at' => $data['end_at'] ?? null,
                        ]);

                        $record->update([
                            'status' => 'En Proceso',
                            'review_status' => 'Aceptada',
                            'review_notes' => $data['review_notes'] ?: null,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        $task->user?->notify(new IncidentAssignedNotification($record->fresh(), $task));

                        Notification::make()
                            ->title('Incidencia aprobada y tarea asignada')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Incident $record): bool => $record->review_status !== 'Rechazada')
                    ->form([
                        Textarea::make('review_notes')
                            ->label('Motivo del rechazo')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Incident $record, array $data): void {
                        $record->update([
                            'review_status' => 'Rechazada',
                            'review_notes' => $data['review_notes'],
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Incidencia rechazada')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIncidents::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'tasks', 'reviewer'])
            ->withCount('tasks')
            ->visibleTo(auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
