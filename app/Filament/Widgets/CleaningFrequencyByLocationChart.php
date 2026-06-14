<?php

namespace App\Filament\Widgets;

use App\Models\Facultad;
use App\Models\Task;
use App\Support\TaskDashboardFilters;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class CleaningFrequencyByLocationChart extends ChartWidget
{
    use HasFiltersSchema;
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.cleaning-frequency-by-location-chart';

    protected ?string $heading = 'Limpiezas por ubicacion';

    protected ?string $maxHeight = '500px';

    protected int | string | array $columnSpan = 'full';

    public function filtersSchema(Schema $schema): Schema
    {
        $user = auth()->user();

        $options = ['all' => 'Todas las facultades'];

        if (! $user->isSupervisor()) {
            $options['ep'] = 'Empresa Pública EP';
        }

        Facultad::orderBy('name')->get()->each(function (Facultad $f) use (&$options): void {
            $options[(string) $f->id] = $f->display_name;
        });

        return $schema->schema([
            Select::make('facultad_id')
                ->label($user->isSupervisor() ? 'Facultad' : 'Facultad / Empresa')
                ->placeholder('Todas las facultades')
                ->options($options)
                ->searchable()
                ->native(false),
        ]);
    }

    protected function getData(): array
    {
        $facultadId  = $this->filters['facultad_id'] ?? null;
        $pageFilters = $this->pageFilters ?? [];

        $query = TaskDashboardFilters::apply(
            Task::query()->visibleTo(auth()->user()),
            $pageFilters
        );

        if ($facultadId === 'ep') {
            $query->whereHas('user', fn ($q) => $q->where('tipo_conserje', 'ep'));
        } elseif (filled($facultadId) && $facultadId !== 'all') {
            $query->whereHas('user', fn ($q) => $q->where('facultad_id', (int) $facultadId));
        }

        $locations = $query
            ->select('location', DB::raw('COUNT(*) as total'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label'               => 'Limpiezas',
                    'data'                => $locations->pluck('total')->all(),
                    'backgroundColor'     => '#4A6CF7',
                    'hoverBackgroundColor' => '#2D3FE0',
                    'borderRadius'        => 8,
                    'borderSkipped'       => false,
                    'barThickness'        => 18,
                ],
            ],
            'labels' => $locations->pluck('location')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis'   => 'y',
            'aspectRatio' => 1,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#1A1F36',
                    'titleColor'      => '#ffffff',
                    'bodyColor'       => '#8F95B2',
                    'padding'         => 10,
                    'cornerRadius'    => 8,
                    'displayColors'   => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'border'      => ['display' => false],
                    'ticks'       => [
                        'precision' => 0,
                        'color'     => '#94a3b8',
                        'font'      => ['size' => 11],
                    ],
                    'grid' => [
                        'color'     => 'rgba(148,163,184,0.10)',
                        'drawTicks' => false,
                    ],
                ],
                'y' => [
                    'border' => ['display' => false],
                    'ticks'  => [
                        'autoSkip' => false,
                        'color'    => '#475569',
                        'font'     => ['size' => 12, 'weight' => '500'],
                        'padding'  => 8,
                    ],
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
