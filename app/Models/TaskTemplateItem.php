<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplateItem extends Model
{
    public const DAY_LABELS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $fillable = [
        'task_template_id',
        'day_of_week',
        'title',
        'description',
        'location',
        'priority',
        'start_time',
        'end_time',
        'all_day',
    ];

    protected $casts = [
        'all_day'     => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function taskTemplate(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class);
    }
}
