<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use App\Support\TaskDashboardFilters;
use App\Support\TaskResourceUrl;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaskOverviewStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Resumen operativo';

    protected ?string $description = 'Estado actual de las tareas y del personal operativo.';

    protected function getStats(): array
    {
        $filters = $this->pageFilters;
        $baseQuery = TaskDashboardFilters::apply(Task::query()->visibleTo(auth()->user()), $filters);
        $activeTasksQuery = TaskDashboardFilters::apply(
            Task::query()->visibleTo(auth()->user())->where('status', '!=', 'Completada'),
            $filters
        );
        $today = now()->startOfDay();
        $baseUrl = TaskResourceUrl::filtered($filters ?? []);

        $totalTasks = (clone $baseQuery)->count();
        $pendingTasks = (clone $baseQuery)->where('status', 'Pendiente')->count();
        $inProgressTasks = (clone $baseQuery)->where('status', 'En Proceso')->count();
        $completedTasks = (clone $baseQuery)->where('status', 'Completada')->count();
        $overdueTasks = (clone $activeTasksQuery)->whereDate('due_date', '<', $today)->count();
        $highPriorityTasks = (clone $activeTasksQuery)->where('priority', 'Alta')->count();
        $conserjesQuery = User::queryConserjes(tipoConserje: $filters['tipo_conserje'] ?? null);
        $activeConserjes = filled($filters['user_id'] ?? null)
            ? (clone $conserjesQuery)->whereKey($filters['user_id'])->count()
            : $conserjesQuery->count();

        return [
            Stat::make('Tareas totales', $totalTasks)
                ->description('Base completa de tareas registradas')
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('primary')
                ->url($baseUrl),
            Stat::make('Pendientes', $pendingTasks)
                ->description('Por atender')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('gray')
                ->url(TaskResourceUrl::filtered($filters ?? [], ['status' => 'Pendiente'])),
            Stat::make('En proceso', $inProgressTasks)
                ->description('Actualmente en ejecución')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->url(TaskResourceUrl::filtered($filters ?? [], ['status' => 'En Proceso'])),
            Stat::make('Completadas', $completedTasks)
                ->description('Cerradas correctamente')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->url(TaskResourceUrl::filtered($filters ?? [], ['status' => 'Completada'])),
            Stat::make('Vencidas', $overdueTasks)
                ->description('No completadas fuera de fecha')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdueTasks > 0 ? 'danger' : 'success')
                ->url(TaskResourceUrl::filtered($filters ?? [], ['overdue' => true])),
            Stat::make('Prioridad alta activas', $highPriorityTasks)
                ->description('Requieren seguimiento cercano')
                ->descriptionIcon(Heroicon::OutlinedBolt)
                ->color($highPriorityTasks > 0 ? 'danger' : 'success')
                ->url(TaskResourceUrl::filtered($filters ?? [], ['priority' => 'Alta', 'active_only' => true])),
            Stat::make('Conserjes activos', $activeConserjes)
                ->description('Disponibles para asignación')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('info'),
        ];
    }
}
