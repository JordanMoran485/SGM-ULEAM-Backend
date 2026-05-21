<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ConserjeWorkloadWidget;
use App\Filament\Widgets\CleaningFrequencyByLocationChart;
use App\Filament\Widgets\CleaningLocationSummaryTable;
use App\Filament\Widgets\TaskAlertsWidget;
use App\Filament\Widgets\TaskOverviewStats;
use App\Filament\Widgets\TaskStatusPriorityChart;
use App\Filament\Widgets\TodayTasksWidget;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Escritorio';

    protected static ?string $navigationLabel = 'Escritorio';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 0;

    public function getWidgets(): array
    {
        return [
            TaskOverviewStats::class,
            TaskStatusPriorityChart::class,
            CleaningFrequencyByLocationChart::class,
            CleaningLocationSummaryTable::class,
            TodayTasksWidget::class,
            TaskAlertsWidget::class,
            ConserjeWorkloadWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Conserje')
                ->options(fn (): array => User::conserjeOptions())
                ->searchable()
                ->preload(),
            Select::make('status')
                ->label('Estado')
                ->options([
                    'Pendiente' => 'Pendiente',
                    'En Proceso' => 'En Proceso',
                    'Completada' => 'Completada',
                ]),
            Select::make('priority')
                ->label('Prioridad')
                ->options([
                    'Alta' => 'Alta',
                    'Media' => 'Media',
                    'Baja' => 'Baja',
                ]),
            DatePicker::make('from')
                ->label('Desde')
                ->native(false),
            DatePicker::make('until')
                ->label('Hasta')
                ->native(false),
        ]);
    }
}
