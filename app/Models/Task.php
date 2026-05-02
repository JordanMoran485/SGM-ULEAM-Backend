<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'status',
        'priority',
        'location',
        'all_day',
        'due_date',
        'start_at',
        'end_at',
        'color',
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'due_date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            if ($task->start_at && $task->all_day) {
                $task->start_at = $task->start_at->copy()->startOfDay();
            }

            if ($task->start_at && $task->all_day) {
                $task->end_at = $task->start_at->copy()->addDay()->startOfDay();
            }

            if ($task->start_at) {
                $task->due_date = $task->start_at->toDateString();
            }

            if (! $task->start_at && $task->due_date) {
                $task->start_at = $task->due_date->startOfDay();
            }

            if (! $task->all_day && ! $task->end_at && $task->start_at) {
                $task->end_at = $task->start_at->copy();
            }

            if ($task->end_at && $task->start_at && $task->end_at->lt($task->start_at)) {
                $task->end_at = $task->start_at->copy();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
