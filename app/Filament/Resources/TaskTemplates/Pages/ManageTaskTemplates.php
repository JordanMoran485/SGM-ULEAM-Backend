<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use App\Models\Task;
use App\Models\TaskTemplate;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageTaskTemplates extends ManageRecords
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
                ->modalDescription('Se crearán tareas para todos los conserjes según sus plantillas. Las tareas que ya existan con el mismo título para ese día serán omitidas.')
                ->modalSubmitActionLabel('Generar tareas')
                ->form([
                    DatePicker::make('week_date')
                        ->label('Selecciona cualquier día de la semana objetivo')
                        ->required()
                        ->native(false)
                        ->default(now()),
                ])
                ->action(function (array $data): void {
                    $weekStart = Carbon::parse($data['week_date'])->startOfWeek(Carbon::MONDAY);

                    $templates = TaskTemplate::query()
                        ->with('user')
                        ->visibleTo(auth()->user())
                        ->get();

                    if ($templates->isEmpty()) {
                        Notification::make()
                            ->title('Sin plantillas')
                            ->body('No hay plantillas configuradas para generar tareas.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $created = 0;
                    $skipped = 0;

                    foreach ($templates as $template) {
                        $taskDate = $weekStart->copy()->addDays($template->day_of_week - 1);

                        $alreadyExists = Task::query()
                            ->where('user_id', $template->user_id)
                            ->where('title', $template->title)
                            ->whereDate('start_at', $taskDate->toDateString())
                            ->exists();

                        if ($alreadyExists) {
                            $skipped++;
                            continue;
                        }

                        $startAt = $template->all_day
                            ? $taskDate->copy()->startOfDay()
                            : $taskDate->copy()->setTimeFromTimeString($template->start_time ?? '08:00');

                        $endAt = (! $template->all_day && $template->end_time)
                            ? $taskDate->copy()->setTimeFromTimeString($template->end_time)
                            : null;

                        Task::create([
                            'user_id'     => $template->user_id,
                            'title'       => $template->title,
                            'description' => $template->description,
                            'location'    => $template->location,
                            'priority'    => $template->priority,
                            'status'      => 'Pendiente',
                            'start_at'    => $startAt,
                            'end_at'      => $endAt,
                            'all_day'     => $template->all_day,
                        ]);

                        $created++;
                    }

                    $weekLabel = $weekStart->format('d/m/Y')
                        . ' – '
                        . $weekStart->copy()->addDays(6)->format('d/m/Y');

                    $body = "Se crearon {$created} " . ($created === 1 ? 'tarea' : 'tareas') . " para la semana del {$weekLabel}.";

                    if ($skipped > 0) {
                        $body .= " {$skipped} " . ($skipped === 1 ? 'fue omitida' : 'fueron omitidas') . ' por ya existir.';
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
