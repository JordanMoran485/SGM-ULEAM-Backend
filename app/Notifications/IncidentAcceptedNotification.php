<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\Task;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Incident $incident,
        protected Task $task,
        protected ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => 'Incidencia aprobada',
            'body'        => $this->notes
                ? "Tu reporte \"{$this->incident->title}\" fue aprobado. Nota: {$this->notes}"
                : "Tu reporte \"{$this->incident->title}\" fue aprobado por el supervisor.",
            'incident_id'       => $this->incident->getKey(),
            'task_id'           => $this->task->getKey(),
            'location'          => $this->incident->location,
            'priority'          => $this->task->priority,
            'status'            => $this->task->status,
            'notification_type' => 'incident_accepted',
        ];
    }

    public function toExpoPush(object $notifiable): array
    {
        return [
            'title' => 'Incidencia aprobada',
            'body'  => $this->notes
                ? "Tu reporte \"{$this->incident->title}\" fue aprobado. Nota: {$this->notes}"
                : "Tu reporte \"{$this->incident->title}\" fue aprobado por el supervisor.",
            'sound' => 'default',
            'data'  => [
                'incident_id' => $this->incident->getKey(),
                'task_id'     => $this->task->getKey(),
                'location'    => $this->incident->location,
                'priority'    => $this->task->priority,
                'status'      => $this->task->status,
                'type'        => 'incident',
            ],
        ];
    }
}
