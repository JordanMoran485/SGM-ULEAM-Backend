<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'image',
        'status',
        'review_status',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
        'priority',
        'location',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
