<?php

namespace App\Filament\Resources\TaskTemplates\Pages\Concerns;

use App\Models\TaskTemplate;
use App\Models\TaskTemplateItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

trait HasDayRepeaters
{
    protected function dayRepeater(int $day, string $label): Repeater
    {
        return Repeater::make("day_{$day}")
            ->hiddenLabel()
            ->schema([
                TextInput::make('title')
                    ->label('Tarea')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options(['Baja' => 'Baja', 'Media' => 'Media', 'Alta' => 'Alta'])
                    ->default('Media')
                    ->required()
                    ->columnSpan(1),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->maxLength(255)
                    ->columnSpan(3),
                Toggle::make('all_day')
                    ->label('Todo el día')
                    ->live()
                    ->columnSpan(1),
                TimePicker::make('start_time')
                    ->label('Inicio')
                    ->seconds(false)
                    ->required(fn (Get $get): bool => ! $get('all_day'))
                    ->hidden(fn (Get $get): bool => (bool) $get('all_day'))
                    ->columnSpan(1),
                TimePicker::make('end_time')
                    ->label('Fin')
                    ->seconds(false)
                    ->hidden(fn (Get $get): bool => (bool) $get('all_day'))
                    ->columnSpan(1),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->addActionLabel("+ Agregar tarea del {$label}")
            ->defaultItems(0)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
            ->afterStateHydrated(function (Repeater $component, ?Model $record) use ($day): void {
                if (! $record instanceof TaskTemplate) {
                    $component->state([]);

                    return;
                }

                $items = $record->taskTemplateItems()
                    ->where('day_of_week', $day)
                    ->orderBy('start_time')
                    ->get()
                    ->map(fn (TaskTemplateItem $item): array => [
                        'title'       => $item->title,
                        'priority'    => $item->priority,
                        'location'    => $item->location,
                        'all_day'     => $item->all_day,
                        'start_time'  => $item->start_time,
                        'end_time'    => $item->end_time,
                        'description' => $item->description,
                    ])
                    ->toArray();

                $component->state($items);
            });
    }
}
