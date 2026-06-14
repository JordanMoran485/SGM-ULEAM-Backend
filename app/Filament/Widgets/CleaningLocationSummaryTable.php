<?php

namespace App\Filament\Widgets;

use App\Models\Facultad;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskDashboardFilters;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
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

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultKeySort(false)
            ->paginated([10, 25, 50])
            ->filters([
                Filter::make('ubicacion_filters')
                    ->form([
                        Select::make('facultad_id')
                            ->label('Facultad / Empresa')
                            ->placeholder('Todas')
                            ->options(function (): array {
                                $facultades = Facultad::orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Facultad $f): array => [$f->id => $f->display_name])
                                    ->all();

                                return ['ep' => '— Empresa Pública EP'] + $facultades;
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('conserje_id', null)),

                        Select::make('conserje_id')
                            ->label('Conserje')
                            ->placeholder('Todos los conserjes')
                            ->searchable()
                            ->options(function (Get $get): array {
                                $facultadId = $get('facultad_id');

                                return User::queryConserjes()
                                    ->when(
                                        $facultadId === 'ep',
                                        fn (Builder $q): Builder => $q->where('tipo_conserje', 'ep')
                                    )
                                    ->when(
                                        filled($facultadId) && $facultadId !== 'ep',
                                        fn (Builder $q): Builder => $q->where('facultad_id', $facultadId)
                                    )
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (User $u): array => [$u->id => $u->getDisplayNameWithConserjeType()])
                                    ->all();
                            })
                            ->live(),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        $facultadId = $data['facultad_id'] ?? null;

                        return $query
                            ->when(
                                $facultadId === 'ep',
                                fn (Builder $q): Builder => $q->whereHas(
                                    'user',
                                    fn (Builder $uq): Builder => $uq->where('tipo_conserje', 'ep')
                                )
                            )
                            ->when(
                                filled($facultadId) && $facultadId !== 'ep',
                                fn (Builder $q): Builder => $q->whereHas(
                                    'user',
                                    fn (Builder $uq): Builder => $uq->where('facultad_id', $facultadId)
                                )
                            )
                            ->when(
                                filled($data['conserje_id'] ?? null),
                                fn (Builder $q): Builder => $q->where('tasks.user_id', $data['conserje_id'])
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['facultad_id'] ?? null)) {
                            if ($data['facultad_id'] === 'ep') {
                                $indicators[] = 'Empresa Pública EP';
                            } else {
                                $f = Facultad::find($data['facultad_id']);
                                if ($f) $indicators[] = 'Facultad: ' . $f->display_name;
                            }
                        }

                        if (filled($data['conserje_id'] ?? null)) {
                            $u = User::find($data['conserje_id']);
                            if ($u) $indicators[] = 'Conserje: ' . $u->full_name;
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->columns([
                TextColumn::make('location')
                    ->label('Ubicacion')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('total')
                    ->label('Limpiezas')
                    ->alignCenter(),
                TextColumn::make('cleaning_days')
                    ->label('Dias activos')
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
                'tasks.location',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT tasks.due_date) as cleaning_days'),
                DB::raw('MAX(tasks.due_date) as last_cleaning_date')
            )
            ->whereNotNull('tasks.location')
            ->where('tasks.location', '!=', '')
            ->groupBy('tasks.location')
            ->orderByDesc('total')
            ->orderBy('tasks.location');
    }

    public function getTableRecordKey(Model | array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['location'] ?? '');
        }

        return (string) $record->getAttribute('location');
    }
}
