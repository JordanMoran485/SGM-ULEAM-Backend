<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Support\TaskDashboardFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class TaskStatusPriorityChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Distribución por estado y prioridad';

    protected ?string $description = 'Vista rápida del volumen actual por categoría.';

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getData(): array
    {
        $filters = $this->pageFilters;

        return [
            'datasets' => [
                [
                    'label' => 'Tareas',
                    'data' => [
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('status', 'Pendiente')->count(),
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('status', 'En Proceso')->count(),
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('status', 'Completada')->count(),
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('priority', 'Alta')->count(),
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('priority', 'Media')->count(),
                        TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters)->where('priority', 'Baja')->count(),
                    ],
                    'backgroundColor' => [
                        '#94a3b8',
                        '#f59e0b',
                        '#22c55e',
                        '#dc2626',
                        '#3b82f6',
                        '#14b8a6',
                    ],
                ],
            ],
            'labels' => [
                'Pendiente',
                'En Proceso',
                'Completada',
                'Prioridad Alta',
                'Prioridad Media',
                'Prioridad Baja',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
