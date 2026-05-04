<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TasksCalendarWidget extends FullCalendarWidget
{
    use InteractsWithPageFilters;

    public Model | string | null $model = Task::class;

    protected ?string $heading = 'Calendario de tareas';

    public function updatedPageFilters(): void
    {
        $this->refreshRecords();
    }

    public function fetchEvents(array $info): array
    {
        $rangeStart = Carbon::parse($info['start']);
        $rangeEnd = Carbon::parse($info['end']);

        return $this->getFilteredTaskQuery()
            ->where(function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query
                    ->whereBetween('start_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('end_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function (Builder $inner) use ($rangeStart, $rangeEnd): void {
                        $inner
                            ->where('start_at', '<=', $rangeStart)
                            ->where('end_at', '>=', $rangeEnd);
                    });
            })
            ->with('user')
            ->get()
            ->map(fn (Task $task): array => [
                'id' => (string) $task->getKey(),
                'title' => $this->formatEventTitle($task),
                'start' => optional($task->start_at)->toIso8601String(),
                'end' => optional($task->end_at)->toIso8601String(),
                'allDay' => (bool) $task->all_day,
                'backgroundColor' => $this->getPriorityColor($task->priority),
                'borderColor' => $this->getPriorityColor($task->priority),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'location' => $task->location,
                    'conserje' => $task->user?->name,
                ],
            ])
            ->all();
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Titulo')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Descripcion')
                ->rows(4)
                ->maxLength(1000),
            Forms\Components\Select::make('user_id')
                ->label('Conserje')
                ->options(fn (): array => User::conserjeOptions())
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'Pendiente' => 'Pendiente',
                    'En Proceso' => 'En Proceso',
                    'Completada' => 'Completada',
                ])
                ->default('Pendiente')
                ->required(),
            Forms\Components\Select::make('priority')
                ->label('Prioridad')
                ->options([
                    'Baja' => 'Baja',
                    'Media' => 'Media',
                    'Alta' => 'Alta',
                ])
                ->default('Media')
                ->required(),
            Forms\Components\TextInput::make('location')
                ->label('Ubicacion')
                ->maxLength(255),
            Forms\Components\Toggle::make('all_day')
                ->label('Todo el dia')
                ->default(true),
            Forms\Components\DateTimePicker::make('start_at')
                ->label('Inicio')
                ->native(false)
                ->seconds(false)
                ->required(),
            Forms\Components\DateTimePicker::make('end_at')
                ->label('Fin')
                ->native(false)
                ->seconds(false),
        ];
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'weekends' => true,
            'nowIndicator' => true,
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mountUsing(function (Schema $schema, array $arguments): void {
                    $start = isset($arguments['start']) ? Carbon::parse($arguments['start']) : now();
                    $end = isset($arguments['end']) ? Carbon::parse($arguments['end']) : $start->copy()->addDay();
                    $allDay = $arguments['allDay'] ?? true;

                    $schema->fill([
                        'start_at' => $allDay ? $start->copy()->startOfDay() : $start,
                        'end_at' => $allDay ? $start->copy()->addDay()->startOfDay() : $end,
                        'all_day' => $allDay,
                        'status' => 'Pendiente',
                        'priority' => 'Media',
                    ]);
                }),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mountUsing(function (Task $record, Schema $schema, array $arguments): void {
                    $schema->fill([
                        'title' => $record->title,
                        'description' => $record->description,
                        'user_id' => $record->user_id,
                        'status' => $record->status,
                        'priority' => $record->priority,
                        'location' => $record->location,
                        'all_day' => $arguments['event']['allDay'] ?? $record->all_day,
                        'start_at' => $arguments['event']['start'] ?? $record->start_at,
                        'end_at' => $arguments['event']['end'] ?? $record->end_at,
                    ]);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFilteredTaskQuery(): Builder
    {
        $query = Task::query()
            ->visibleTo(auth()->user())
            ->whereNotNull('start_at');

        $filters = $this->pageFilters ?? [];

        if (filled($filters['user_id'] ?? null)) {
            $query->where('user_id', $filters['user_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('start_at', '>=', $filters['from']);
        }

        if (filled($filters['until'] ?? null)) {
            $query->whereDate('start_at', '<=', $filters['until']);
        }

        return $query;
    }

    protected function formatEventTitle(Task $task): string
    {
        $assignee = $task->user?->name;

        return $assignee
            ? "{$task->title} - {$assignee}"
            : $task->title;
    }

    protected function getPriorityColor(?string $priority): string
    {
        return match ($priority) {
            'Alta' => '#dc2626',
            'Media' => '#f59e0b',
            'Baja' => '#2563eb',
            default => '#64748b',
        };
    }
}
