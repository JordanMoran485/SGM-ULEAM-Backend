<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Incident $incident,
        protected string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Incidencia rechazada',
            'body' => "Tu reporte \"{$this->incident->title}\" fue rechazado. Motivo: {$this->reason}",
            'incident_id' => $this->incident->getKey(),
            'location' => $this->incident->location,
        ];
    }
}
