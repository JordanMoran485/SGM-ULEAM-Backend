<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $modelLabel = 'Tarea';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 2;

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
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Select::make('tipo_conserje')
                    ->label('Tipo de conserje')
                    ->options(User::conserjeTypeOptions())
                    ->placeholder('Todos')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),
                Select::make('user_id')
                    ->label('Asignar a conserje')
                    ->options(fn (Get $get): array => User::conserjeOptions(tipoConserje: $get('tipo_conserje')))
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->label('Estado de la tarea')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ])
                    ->default('Pendiente')
                    ->required()
                    ->hiddenOn('create'),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'Baja' => 'Baja',
                        'Media' => 'Media',
                        'Alta' => 'Alta',
                    ])
                    ->default('Media')
                    ->required(),
                TextInput::make('location')
                    ->label('Ubicacion')
                    ->maxLength(255),
                Toggle::make('all_day')
                    ->label('Todo el día')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('end_at', null)),
                DateTimePicker::make('start_at')
                    ->label('Inicio')
                    ->native(false)
                    ->seconds(false)
                    ->hidden(fn (Get $get): bool => (bool) $get('all_day'))
                    ->required(fn (Get $get): bool => ! (bool) $get('all_day'))
                    ->rule(fn (Get $get, ?Model $record): \Closure =>
                        function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                            $userId = $get('user_id');
                            if (! $value || ! $userId || (bool) $get('all_day')) {
                                return;
                            }

                            $startAt    = Carbon::parse($value);
                            $endRaw     = $get('end_at');
                            $checkEndAt = ($endRaw && ! Carbon::parse($endRaw)->equalTo($startAt))
                                ? Carbon::parse($endRaw)
                                : $startAt->copy()->addHour();

                            $overlap = Task::query()
                                ->where('user_id', $userId)
                                ->where('all_day', false)
                                ->when($record?->getKey(), fn (Builder $q) => $q->where('id', '!=', $record->getKey()))
                                ->whereDate('start_at', $startAt->toDateString())
                                ->where('status', '!=', 'Completada')
                                ->get(['id', 'start_at', 'end_at'])
                                ->contains(function (Task $existing) use ($startAt, $checkEndAt): bool {
                                    $exStart = Carbon::parse($existing->start_at);

                                    if ($startAt->equalTo($exStart)) {
                                        return true;
                                    }

                                    $exEnd = Carbon::parse($existing->end_at ?? $exStart);
                                    if ($exEnd->equalTo($exStart)) {
                                        $exEnd = $exStart->copy()->addHour();
                                    }

                                    return $startAt->lt($exEnd) && $checkEndAt->gt($exStart);
                                });

                            if ($overlap) {
                                $fail('El conserje ya tiene una tarea asignada en ese horario.');
                            }
                        }
                    ),
                DateTimePicker::make('end_at')
                    ->label('Fin')
                    ->native(false)
                    ->seconds(false)
                    ->hidden(fn (Get $get): bool => (bool) $get('all_day')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Titulo'),
                TextEntry::make('description')
                    ->label('Descripcion')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('user.name')
                    ->label('Conserje')
                    ->formatStateUsing(fn ($state, Task $record): string => $record->user?->getDisplayNameWithConserjeType() ?? '-')
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
                TextEntry::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    }),
                TextEntry::make('location')
                    ->label('Ubicacion')
                    ->placeholder('-'),
                TextEntry::make('all_day')
                    ->label('Todo el dia')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Si' : 'No'),
                TextEntry::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Conserje')
                    ->formatStateUsing(fn ($state, Task $record): string => $record->user?->getDisplayNameWithConserjeType() ?? '-')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label('Ubicacion')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('conserje_filters')
                    ->form([
                        Select::make('tipo_conserje')
                            ->label('Tipo de conserje')
                            ->options(function (): array {
                                $user = auth()->user();

                                if ($user->isSuperAdmin() || $user->isSupervisorGeneral()) {
                                    return User::conserjeTypeOptions();
                                }

                                return ['uleam' => 'Uleam'];
                            })
                            ->placeholder('Seleccionar...')
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),

                        Select::make('user_id')
                            ->label('Conserje')
                            ->options(fn (Get $get): array => User::conserjeOptions(
                                tipoConserje: $get('tipo_conserje')
                            ))
                            ->placeholder(fn (Get $get): string => blank($get('tipo_conserje'))
                                ? 'Selecciona un tipo primero'
                                : 'Todos'
                            )
                            ->disabled(fn (Get $get): bool => blank($get('tipo_conserje')))
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['tipo_conserje'] ?? null)) {
                            $query->whereHas(
                                'user',
                                fn (Builder $q): Builder => $q->where('tipo_conserje', $data['tipo_conserje'])
                            );
                        }

                        if (filled($data['user_id'] ?? null)) {
                            $query->where('user_id', $data['user_id']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['tipo_conserje'] ?? null)) {
                            $indicators[] = 'Tipo: ' . (User::conserjeTypeOptions()[$data['tipo_conserje']] ?? $data['tipo_conserje']);
                        }

                        if (filled($data['user_id'] ?? null)) {
                            $user = User::find($data['user_id']);
                            if ($user) {
                                $indicators[] = 'Conserje: ' . $user->full_name;
                            }
                        }

                        return $indicators;
                    }),

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
                Filter::make('schedule')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('start_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('start_at', '<=', $data['until']),
                            );
                    }),
                Filter::make('today')
                    ->label('Solo hoy')
                    ->query(fn (Builder $query): Builder => $query->whereDate('start_at', now()->toDateString())),
                Filter::make('active_only')
                    ->label('Solo activas')
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', 'Completada')),
                Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('status', '!=', 'Completada')
                            ->whereDate('due_date', '<', now()->toDateString())
                    ),
                Filter::make('attention_required')
                    ->label('Atención requerida')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('status', '!=', 'Completada')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereDate('due_date', '<', now()->toDateString())
                                    ->orWhere('priority', 'Alta');
                            })
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->after(function (Task $record): void {
                        $n = Notification::make()->success()->title('Tarea actualizada')->body($record->title);
                        auth()->user()->notifyNow($n->toDatabase());
                    }),
                DeleteAction::make()
                    ->after(function (Task $record): void {
                        $n = Notification::make()->danger()->title('Tarea eliminada')->body($record->title);
                        auth()->user()->notifyNow($n->toDatabase());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (): void {
                            $n = Notification::make()->danger()->title('Tareas eliminadas');
                            auth()->user()->notifyNow($n->toDatabase());
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->visibleTo(auth()->user());
    }
}
