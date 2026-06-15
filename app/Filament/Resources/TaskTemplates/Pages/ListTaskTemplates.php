<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Notifications\WeekTasksSummaryNotification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

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
                        ->with(['taskTemplateItems', 'user'])
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

                    $weekLabel = $weekStart->format('d/m/Y')
                        . ' – '
                        . $weekStart->copy()->addDays(6)->format('d/m/Y');

                    $created        = 0;
                    $skipped        = 0;
                    $tasksByUser    = [];

                    foreach ($templates as $template) {
                        foreach ($template->taskTemplateItems as $item) {
                            $taskDate = $weekStart->copy()->addDays($item->day_of_week - 1);

                            $startAt = $item->all_day
                                ? $taskDate->copy()->startOfDay()
                                : $taskDate->copy()->setTimeFromTimeString($item->start_time ?? '08:00');

                            $endAt = (! $item->all_day && $item->end_time)
                                ? $taskDate->copy()->setTimeFromTimeString($item->end_time)
                                : null;

                            // End time used only for overlap calculation (never null)
                            $checkEndAt = $item->all_day
                                ? $taskDate->copy()->endOfDay()
                                : ($item->end_time
                                    ? $taskDate->copy()->setTimeFromTimeString($item->end_time)
                                    : $startAt->copy()->addHour());

                            $dayTasks = Task::query()
                                ->where('user_id', $template->user_id)
                                ->whereDate('start_at', $taskDate->toDateString())
                                ->where('status', '!=', 'Completada')
                                ->get(['start_at', 'end_at', 'all_day']);

                            $hasOverlap = $dayTasks->contains(function (Task $existing) use ($startAt, $checkEndAt): bool {
                                $exStart = Carbon::parse($existing->start_at);

                                // Same start time always conflicts
                                if ($startAt->equalTo($exStart)) {
                                    return true;
                                }

                                $exEnd = $existing->end_at
                                    ? Carbon::parse($existing->end_at)
                                    : $exStart->copy()->addHour();

                                // Model saves end_at = start_at when no end_time; treat as 1 hour
                                if ($exEnd->equalTo($exStart)) {
                                    $exEnd = $exStart->copy()->addHour();
                                }

                                return $startAt->lt($exEnd) && $checkEndAt->gt($exStart);
                            });

                            if ($hasOverlap) {
                                $skipped++;
                                continue;
                            }

                            $task = Task::create([
                                'user_id'          => $template->user_id,
                                'task_template_id' => $template->getKey(),
                                'title'            => $item->title,
                                'description'      => $item->description,
                                'location'         => $item->location,
                                'priority'         => $item->priority,
                                'status'           => 'Pendiente',
                                'start_at'         => $startAt,
                                'end_at'           => $endAt,
                                'all_day'          => $item->all_day,
                            ]);

                            $tasksByUser[$template->user_id]['user']    = $template->user;
                            $tasksByUser[$template->user_id]['tasks'][] = $task;
                            $created++;
                        }
                    }

                    foreach ($tasksByUser as ['user' => $user, 'tasks' => $tasks]) {
                        $user->notify(new WeekTasksSummaryNotification(
                            collect($tasks),
                            $weekLabel,
                        ));
                    }

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

            Action::make('revertWeek')
                ->label('Revertir semana')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->modalHeading('Revertir tareas de la semana')
                ->modalDescription('Elimina las tareas que fueron generadas desde plantilla para los conserjes y semana seleccionados. Las tareas creadas manualmente no se verán afectadas.')
                ->modalSubmitActionLabel('Eliminar tareas')
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
                        ->label('Semana a revertir')
                        ->helperText('Selecciona cualquier día de la semana que quieres eliminar.')
                        ->required()
                        ->native(false)
                        ->default(now()),
                ])
                ->action(function (array $data): void {
                    $weekStart = Carbon::parse($data['week_date'])->startOfWeek(Carbon::MONDAY);
                    $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

                    $templateIds = TaskTemplate::query()
                        ->visibleTo(auth()->user())
                        ->whereIn('user_id', $data['user_ids'])
                        ->pluck('id');

                    $deleted = Task::query()
                        ->whereIn('task_template_id', $templateIds)
                        ->whereBetween('start_at', [$weekStart, $weekEnd])
                        ->count();

                    Task::query()
                        ->whereIn('task_template_id', $templateIds)
                        ->whereBetween('start_at', [$weekStart, $weekEnd])
                        ->delete();

                    $weekLabel = $weekStart->format('d/m/Y') . ' – ' . $weekEnd->format('d/m/Y');

                    if ($deleted === 0) {
                        Notification::make()
                            ->title('Nada que revertir')
                            ->body("No se encontraron tareas generadas desde plantilla para la semana del {$weekLabel}.")
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Semana revertida')
                        ->body("Se eliminaron {$deleted} " . ($deleted === 1 ? 'tarea' : 'tareas') . " de la semana del {$weekLabel}.")
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Nueva plantilla'),
        ];
    }
}
