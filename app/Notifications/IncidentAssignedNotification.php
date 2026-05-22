<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Incident $incident,
        protected Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Área pendiente de limpieza',
            'body' => "El supervisor aprobó la incidencia \"{$this->incident->title}\". Debes atender el área reportada.",
            'incident_id' => $this->incident->getKey(),
            'task_id' => $this->task->getKey(),
            'location' => $this->incident->location,
            'priority' => $this->task->priority,
            'status' => $this->task->status,
        ];
    }
};
