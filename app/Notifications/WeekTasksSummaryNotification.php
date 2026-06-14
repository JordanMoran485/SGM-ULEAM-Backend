<?php

namespace App\Notifications;

use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WeekTasksSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection $tasks,
        protected string $weekLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $count = $this->tasks->count();

        return [
            'title'             => 'Tareas de la semana asignadas',
            'body'              => "Tienes {$count} " . ($count === 1 ? 'tarea' : 'tareas') . " programadas para la semana del {$this->weekLabel}.",
            'week_label'        => $this->weekLabel,
            'task_count'        => $count,
            'tasks'             => $this->tasks->map(fn ($task): array => [
                'id'       => $task->getKey(),
                'title'    => $task->title,
                'location' => $task->location,
                'priority' => $task->priority,
                'start_at' => $task->start_at?->toIso8601String(),
            ])->values()->all(),
            'notification_type' => 'week_summary',
        ];
    }

    public function toExpoPush(object $notifiable): array
    {
        $count = $this->tasks->count();

        return [
            'title' => 'Tareas de la semana',
            'body'  => "Tienes {$count} " . ($count === 1 ? 'tarea' : 'tareas') . " para la semana del {$this->weekLabel}.",
            'sound' => 'default',
            'data'  => [
                'type'       => 'week_summary',
                'week_label' => $this->weekLabel,
                'task_count' => $count,
            ],
        ];
    }
}
