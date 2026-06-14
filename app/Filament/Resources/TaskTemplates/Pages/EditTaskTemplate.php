<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Filament\Resources\TaskTemplates\Pages\Concerns\HasDayRepeaters;
use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateItem;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditTaskTemplate extends EditRecord
{
    use HasDayRepeaters;

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
                    Select::make('tipo_conserje')
                        ->label('Tipo de conserje')
                        ->options(function (): array {
                            $user = auth()->user();

                            if ($user->isSuperAdmin() || $user->isSupervisorGeneral()) {
                                return User::conserjeTypeOptions();
                            }

                            return ['uleam' => 'Uleam'];
                        })
                        ->afterStateHydrated(function (Select $component, ?Model $record): void {
                            if ($record instanceof TaskTemplate) {
                                $component->state($record->user?->tipo_conserje);
                            }
                        })
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),

                    Select::make('user_id')
                        ->label('Conserje asignado')
                        ->options(fn (Get $get): array => User::conserjeOptions(
                            tipoConserje: $get('tipo_conserje')
                        ))
                        ->placeholder(fn (Get $get): string => blank($get('tipo_conserje'))
                            ? 'Selecciona un tipo primero'
                            : 'Selecciona un conserje'
                        )
                        ->disabled(fn (Get $get): bool => blank($get('tipo_conserje')))
                        ->searchable()
                        ->required()
                        ->unique(table: 'task_templates', column: 'user_id', ignoreRecord: true)
                        ->dehydrated(),
                ])
                ->columns(2),

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $itemsByDay = [];
        foreach (array_keys(TaskTemplateItem::DAY_LABELS) as $day) {
            $key = "day_{$day}";
            $itemsByDay[$day] = $data[$key] ?? [];
            unset($data[$key]);
        }

        $record->update($data);

        $record->taskTemplateItems()->delete();

        foreach ($itemsByDay as $day => $items) {
            foreach ($items as $item) {
                $record->taskTemplateItems()->create(array_merge($item, ['day_of_week' => $day]));
            }
        }

        return $record;
    }
}
