<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Support\TaskDashboardFilters;
use App\Support\TaskResourceUrl;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ConserjeWorkloadWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Carga por conserje';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->headerActions([
                Action::make('view_filtered_tasks')
                    ->label('Abrir listado')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(TaskResourceUrl::filtered($this->pageFilters ?? [])),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10])
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->formatStateUsing(fn (string $state, User $record): string => "{$state} {$record->lastname}")
                    ->searchable(),
                TextColumn::make('pending_tasks_count')
                    ->label('Pendientes')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('in_progress_tasks_count')
                    ->label('En proceso')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('completed_tasks_count')
                    ->label('Completadas')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('total_tasks_count')
                    ->label('Total')
                    ->sortable(),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $filters = $this->pageFilters ?? [];

        $query = User::queryConserjes()
            ->withCount([
                'tasks as pending_tasks_count' => fn (Builder $query): Builder => TaskDashboardFilters::apply($query, $filters)->where('status', 'Pendiente'),
                'tasks as in_progress_tasks_count' => fn (Builder $query): Builder => TaskDashboardFilters::apply($query, $filters)->where('status', 'En Proceso'),
                'tasks as completed_tasks_count' => fn (Builder $query): Builder => TaskDashboardFilters::apply($query, $filters)->where('status', 'Completada'),
                'tasks as total_tasks_count' => fn (Builder $query): Builder => TaskDashboardFilters::apply($query, $filters),
            ])
            ->orderByDesc('pending_tasks_count')
            ->orderByDesc('in_progress_tasks_count')
            ->orderBy('name');

        if (filled($filters['user_id'] ?? null)) {
            $query->whereKey($filters['user_id']);
        }

        return $query;
    }
}
