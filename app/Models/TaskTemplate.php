<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskTemplate extends Model
{
    protected $fillable = ['user_id'];

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
                fn (Builder $q): Builder => $q->where('facultad_id', $facultadId)
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public function getDaysCoveredAttribute(): string
    {
        return $this->taskTemplateItems
            ->pluck('day_of_week')
            ->unique()
            ->sort()
            ->map(fn (int $d): string => TaskTemplateItem::DAY_LABELS[$d] ?? '')
            ->join(', ') ?: '—';
    }

    public function getItemsCountAttribute(): int
    {
        return $this->taskTemplateItems->count();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskTemplateItems(): HasMany
    {
        return $this->hasMany(TaskTemplateItem::class);
    }
}
