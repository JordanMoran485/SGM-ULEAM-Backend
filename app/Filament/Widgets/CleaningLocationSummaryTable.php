<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Support\TaskDashboardFilters;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CleaningLocationSummaryTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Detalle por ubicacion';

    protected ?string $description = 'Resumen con total de limpiezas, dias activos y ultima fecha registrada por ubicacion.';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultKeySort(false)
            ->defaultPaginationPageOption(10)
            ->paginated([10])
            ->columns([
                TextColumn::make('location')
                    ->label('Ubicacion')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('total')
                    ->label('Limpiezas')
                    ->alignCenter(),
                TextColumn::make('cleaning_days')
                    ->label('Dias')
                    ->alignCenter(),
                TextColumn::make('last_cleaning_date')
                    ->label('Ultima limpieza')
                    ->date('d/m/Y'),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return TaskDashboardFilters::apply(
            Task::query()->visibleTo(auth()->user()),
            $this->pageFilters ?? []
        )
            ->select(
                'location',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT due_date) as cleaning_days'),
                DB::raw('MAX(due_date) as last_cleaning_date')
            )
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('total')
            ->orderBy('location');
    }

    public function getTableRecordKey(Model | array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['location'] ?? '');
        }

        return (string) $record->getAttribute('location');
    }
}
