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

class TaskAlertsWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Alertas';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->headerActions([
                Action::make('view_all_alerts')
                    ->label('Ver todas')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(TaskResourceUrl::filtered($this->pageFilters ?? [], [
                        'attention_required' => true,
                    ])),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->recordUrl(fn (Task $record): string => TaskResource::getUrl('index'))
            ->columns([
                TextColumn::make('title')
                    ->label('Tarea')
                    ->limit(28)
                    ->weight('bold'),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->placeholder('-')
                    ->limit(20),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return TaskDashboardFilters::apply(
            Task::query()
            ->where('status', '!=', 'Completada')
            ->where(function (Builder $query): void {
                $query
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->orWhere(function (Builder $priorityQuery): void {
                        $priorityQuery
                            ->where('priority', 'Alta')
                            ->where('status', '!=', 'Completada');
                    });
            })
            ->orderByRaw("
                CASE
                    WHEN due_date < CURRENT_DATE THEN 0
                    WHEN priority = 'Alta' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('due_date'),
            $this->pageFilters,
        );
    }
}
