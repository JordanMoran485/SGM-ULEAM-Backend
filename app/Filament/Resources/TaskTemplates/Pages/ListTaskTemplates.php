<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use App\Models\Task;
use App\Models\TaskTemplate;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTaskTemplates extends ListRecords
{
    protected static string $resource = TaskTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateWeek')
                ->label('Generar semana')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('success')
                ->modalHeading('Generar tareas de la semana')
                ->modalDescription('Elige los conserjes que quieres incluir y la semana objetivo. Las tareas que ya existan para ese día serán omitidas.')
                ->modalSubmitActionLabel('Generar tareas')
                ->form([
                    Select::make('user_ids')
                        ->label('Conserjes')
                        ->options(fn (): array => TaskTemplate::query()
                            ->with('user')
                            ->visibleTo(auth()->user())
                            ->get()
                            ->mapWithKeys(fn (TaskTemplate $t): array => [
                                $t->user_id => $t->user?->getDisplayNameWithConserjeType() ?? '—',
                            ])
                            ->all()
                        )
                        ->multiple()
                        ->native(false)
                        ->searchable()
                        ->required(),

                    DatePicker::make('week_date')
                        ->label('Semana objetivo')
                        ->helperText('Selecciona cualquier día de la semana que quieres generar.')
                        ->required()
                        ->native(false)
                        ->default(now()),
                ])
                ->action(function (array $data): void {
                    $weekStart     = Carbon::parse($data['week_date'])->startOfWeek(Carbon::MONDAY);
                    $selectedUsers = $data['user_ids'] ?? [];

                    $templates = TaskTemplate::query()
                        ->with('taskTemplateItems')
                        ->visibleTo(auth()->user())
                        ->whereIn('user_id', $selectedUsers)
                        ->get();

                    if ($templates->isEmpty()) {
                        Notification::make()
                            ->title('Sin plantillas')
                            ->body('Los conserjes seleccionados no tienen plantillas configuradas.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $created = 0;
                    $skipped = 0;

                    foreach ($templates as $template) {
                        foreach ($template->taskTemplateItems as $item) {
                            $taskDate = $weekStart->copy()->addDays($item->day_of_week - 1);

                            $alreadyExists = Task::query()
                                ->where('user_id', $template->user_id)
                                ->where('title', $item->title)
                                ->whereDate('start_at', $taskDate->toDateString())
                                ->exists();

                            if ($alreadyExists) {
                                $skipped++;
                                continue;
                            }

                            $startAt = $item->all_day
                                ? $taskDate->copy()->startOfDay()
                                : $taskDate->copy()->setTimeFromTimeString($item->start_time ?? '08:00');

                            $endAt = (! $item->all_day && $item->end_time)
                                ? $taskDate->copy()->setTimeFromTimeString($item->end_time)
                                : null;

                            Task::create([
                                'user_id'     => $template->user_id,
                                'title'       => $item->title,
                                'description' => $item->description,
                                'location'    => $item->location,
                                'priority'    => $item->priority,
                                'status'      => 'Pendiente',
                                'start_at'    => $startAt,
                                'end_at'      => $endAt,
                                'all_day'     => $item->all_day,
                            ]);

                            $created++;
                        }
                    }

                    $weekLabel = $weekStart->format('d/m/Y')
                        . ' – '
                        . $weekStart->copy()->addDays(6)->format('d/m/Y');

                    $body = "Se crearon {$created} "
                        . ($created === 1 ? 'tarea' : 'tareas')
                        . " para la semana del {$weekLabel}.";

                    if ($skipped > 0) {
                        $body .= " {$skipped} "
                            . ($skipped === 1 ? 'fue omitida' : 'fueron omitidas')
                            . ' por ya existir.';
                    }

                    Notification::make()
                        ->title('Semana generada')
                        ->body($body)
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Nueva plantilla'),
        ];
    }
}
