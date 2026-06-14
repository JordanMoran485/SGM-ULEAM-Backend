<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateItem;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditTaskTemplate extends EditRecord
{
    protected static string $resource = TaskTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conserje')
                ->schema([
                    Select::make('user_id')
                        ->label('Conserje asignado')
                        ->options(fn (): array => \App\Models\User::conserjeOptions())
                        ->disabled()
                        ->dehydrated(),
                ])
                ->columns(1),

            Tabs::make('Días de la semana')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Lunes')->schema([$this->dayRepeater(1, 'Lunes')]),
                    Tab::make('Martes')->schema([$this->dayRepeater(2, 'Martes')]),
                    Tab::make('Miércoles')->schema([$this->dayRepeater(3, 'Miércoles')]),
                    Tab::make('Jueves')->schema([$this->dayRepeater(4, 'Jueves')]),
                    Tab::make('Viernes')->schema([$this->dayRepeater(5, 'Viernes')]),
                    Tab::make('Sábado')->schema([$this->dayRepeater(6, 'Sábado')]),
                    Tab::make('Domingo')->schema([$this->dayRepeater(7, 'Domingo')]),
                ]),
        ]);
    }

    private function dayRepeater(int $day, string $label): Repeater
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
                    ->options([
                        'Baja'  => 'Baja',
                        'Media' => 'Media',
                        'Alta'  => 'Alta',
                    ])
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract day repeater data before updating the record
        $itemsByDay = [];
        foreach (array_keys(TaskTemplateItem::DAY_LABELS) as $day) {
            $key = "day_{$day}";
            $itemsByDay[$day] = $data[$key] ?? [];
            unset($data[$key]);
        }

        $record->update($data);

        // Sync all items: delete existing and recreate from form data
        $record->taskTemplateItems()->delete();

        foreach ($itemsByDay as $day => $items) {
            foreach ($items as $item) {
                $record->taskTemplateItems()->create(array_merge($item, [
                    'day_of_week' => $day,
                ]));
            }
        }

        return $record;
    }
}
