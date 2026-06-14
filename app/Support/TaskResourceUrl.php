<?php

namespace App\Support;

use App\Filament\Resources\Tasks\TaskResource;

class TaskResourceUrl
{
    public static function filtered(array $dashboardFilters = [], array $overrides = []): string
    {
        $filters = array_merge($dashboardFilters, $overrides);
        $tableFilters = [];

        if (filled($filters['user_id'] ?? null)) {
            $tableFilters['user_id'] = ['value' => (string) $filters['user_id']];
        }

        if (filled($filters['tipo_conserje'] ?? null)) {
            $tableFilters['tipo_conserje'] = ['value' => $filters['tipo_conserje']];
        }

        if (filled($filters['status'] ?? null)) {
            $tableFilters['status'] = ['value' => $filters['status']];
        }

        if (filled($filters['priority'] ?? null)) {
            $tableFilters['priority'] = ['value' => $filters['priority']];
        }

        if (filled($filters['from'] ?? null) || filled($filters['until'] ?? null)) {
            $tableFilters['schedule'] = [
                'from' => $filters['from'] ?? null,
                'until' => $filters['until'] ?? null,
            ];
        }

        if ($filters['today'] ?? false) {
            $tableFilters['today'] = ['isActive' => true];
        }

        if ($filters['active_only'] ?? false) {
            $tableFilters['active_only'] = [
                'isActive' => true,
            ];
        }

        if ($filters['overdue'] ?? false) {
            $tableFilters['overdue'] = [
                'isActive' => true,
            ];
        }

        if ($filters['attention_required'] ?? false) {
            $tableFilters['attention_required'] = [
                'isActive' => true,
            ];
        }

        $url = TaskResource::getUrl('index');

        if (! count($tableFilters)) {
            return $url;
        }

        return $url . '?' . http_build_query([
            'filters' => $tableFilters,
        ]);
    }
}
