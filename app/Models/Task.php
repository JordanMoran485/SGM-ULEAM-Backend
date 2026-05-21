<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'incident_id',
        'user_id',
        'image',
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

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            $facultadId = $user->facultad_id;

            if (! $facultadId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas(
                'user',
                fn (Builder $userQuery): Builder => $userQuery->where('facultad_id', $facultadId)
            );
        }

        if ($user->hasRole('conserje')) {
            return $query->where('user_id', $user->getKey());
        }

        return $query->whereRaw('1 = 0');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
