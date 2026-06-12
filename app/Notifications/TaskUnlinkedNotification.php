<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\Task;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskUnlinkedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Incident $incident,
        protected Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => 'Incidencia desvinculada',
            'body'        => "La incidencia \"{$this->incident->title}\" fue rechazada y ya no está asociada a tu tarea.",
            'incident_id' => $this->incident->getKey(),
            'task_id'     => $this->task->getKey(),
            'location'    => $this->incident->location,
        ];
    }

    public function toExpoPush(object $notifiable): array
    {
        return [
            'title' => 'Incidencia desvinculada',
            'body'  => "La incidencia \"{$this->incident->title}\" fue rechazada y ya no está asociada a tu tarea.",
            'sound' => 'default',
            'data'  => [
                'incident_id' => $this->incident->getKey(),
                'task_id'     => $this->task->getKey(),
                'type'        => 'task',
            ],
        ];
    }
}
