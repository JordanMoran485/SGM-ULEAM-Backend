<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class TaskDashboardFilters
{
    public static function apply(Builder $query, ?array $filters, string $dateColumn = 'due_date'): Builder
    {
        $filters ??= [];

        if (filled($filters['user_id'] ?? null)) {
            $query->where('user_id', $filters['user_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['from'] ?? null)) {
            $query->whereDate($dateColumn, '>=', $filters['from']);
        }

        if (filled($filters['until'] ?? null)) {
            $query->whereDate($dateColumn, '<=', $filters['until']);
        }

        return $query;
    }
}
