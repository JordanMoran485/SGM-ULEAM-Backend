<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TasksCalendarWidget;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TasksCalendar extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/calendario-tareas';

    protected static ?string $title = 'Calendario de tareas';

    protected static ?string $navigationLabel = 'Calendario';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return true;
    }

    public function getColumns(): int | array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            TasksCalendarWidget::class,
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
                    'Baja' => 'Baja',
                    'Media' => 'Media',
                    'Alta' => 'Alta',
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
