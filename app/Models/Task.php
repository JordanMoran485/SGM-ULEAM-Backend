<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'incident_id',
        'user_id',
        'task_template_id',
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

        // Runs after the mutations above so end_at is already resolved
        static::saving(function (Task $task): void {
            if ($task->all_day || ! $task->start_at || ! $task->user_id) {
                return;
            }

            $startAt = Carbon::parse($task->start_at);

            // end_at == start_at means "no explicit duration" — treat as 1 hour
            $checkEndAt = ($task->end_at && ! Carbon::parse($task->end_at)->equalTo($startAt))
                ? Carbon::parse($task->end_at)
                : $startAt->copy()->addHour();

            $overlap = static::query()
                ->where('user_id', $task->user_id)
                ->where(fn (Builder $q) => $q->where('all_day', false)->orWhereNull('all_day'))
                ->when($task->exists, fn (Builder $q) => $q->where('id', '!=', $task->getKey()))
                ->whereDate('start_at', $startAt->toDateString())
                ->where('status', '!=', 'Completada')
                ->get(['id', 'start_at', 'end_at'])
                ->contains(function (Task $existing) use ($startAt, $checkEndAt): bool {
                    $exStart = Carbon::parse($existing->start_at);

                    if ($startAt->equalTo($exStart)) {
                        return true;
                    }

                    $exEnd = Carbon::parse($existing->end_at ?? $exStart);
                    if ($exEnd->equalTo($exStart)) {
                        $exEnd = $exStart->copy()->addHour();
                    }

                    return $startAt->lt($exEnd) && $checkEndAt->gt($exStart);
                });

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_at' => 'El conserje ya tiene una tarea asignada en ese horario.',
                ]);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isSupervisorGeneral()) {
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
