<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Support\TaskDashboardFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CleaningFrequencyByLocationChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Limpiezas por ubicacion';

    protected ?string $description = 'Ranking de ubicaciones con mayor cantidad de limpiezas en el periodo filtrado.';

    protected ?string $maxHeight = '360px';

    protected int $visibleLocationsLimit = 10;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];

        $locations = TaskDashboardFilters::apply(
            Task::query()->visibleTo(auth()->user()),
            $filters
        )
            ->select('location', DB::raw('COUNT(*) as total'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('total')
            ->get();

        $locations = $this->groupOverflowLocations($locations);

        return [
            'datasets' => [
                [
                    'label' => 'Limpiezas',
                    'data' => $locations->pluck('total')->all(),
                    'backgroundColor' => '#0f766e',
                    'borderRadius' => 8,
                    'barThickness' => 18,
                ],
            ],
            'labels' => $locations->pluck('location')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function groupOverflowLocations(Collection $locations): Collection
    {
        if ($locations->count() <= $this->visibleLocationsLimit) {
            return $locations;
        }

        $visibleLocations = $locations->take($this->visibleLocationsLimit - 1);
        $overflowLocations = $locations->slice($this->visibleLocationsLimit - 1);

        $visibleLocations->push((object) [
            'location' => 'Otras ubicaciones',
            'total' => $overflowLocations->sum('total'),
        ]);

        return $visibleLocations->values();
    }
}
