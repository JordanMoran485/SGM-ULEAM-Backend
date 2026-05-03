<?php

namespace App\Filament\Widgets;

use App\Support\TaskResourceUrl;
use Filament\Actions\Action;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Support\TaskDashboardFilters;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayTasksWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tareas de hoy';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->headerActions([
                Action::make('view_all_today')
                    ->label('Ver todas')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(
                        TaskResourceUrl::filtered($this->pageFilters ?? [], [
                            'from' => now()->toDateString(),
                            'until' => now()->toDateString(),
                        ])
                    ),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->recordUrl(fn (Task $record): string => TaskResource::getUrl('index'))
            ->columns([
                TextColumn::make('title')
                    ->label('Tarea')
                    ->searchable()
                    ->limit(28)
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Conserje')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime('H:i')
                    ->placeholder('Todo el dia'),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return TaskDashboardFilters::apply(
            Task::query()
                ->with('user')
                ->whereDate('start_at', now()->toDateString())
                ->orderBy('start_at'),
            $this->pageFilters,
            'start_at',
        );
    }
}
