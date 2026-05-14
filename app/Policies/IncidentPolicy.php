<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IncidentPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): bool | null
    {
        if (method_exists($authUser, 'isSuperAdmin') && $authUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'canAccessSystem') && $authUser->canAccessSystem();
    }

    public function view(AuthUser $authUser, Incident $incident): bool
    {
        return $this->canAccessIncident($authUser, $incident);
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, Incident $incident): bool
    {
        return $this->canAccessIncident($authUser, $incident);
    }

    public function delete(AuthUser $authUser, Incident $incident): bool
    {
        return $this->canAccessIncident($authUser, $incident);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'isSupervisor') && $authUser->isSupervisor();
    }

    public function restore(AuthUser $authUser, Incident $incident): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Incident $incident): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Incident $incident): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }

    protected function canAccessIncident(AuthUser $authUser, Incident $incident): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        if ($authUser->isSupervisor()) {
            return $incident->user?->belongsToSameFacultadAs($authUser) ?? false;
        }

        return false;
    }
}
