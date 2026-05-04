<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TaskPolicy
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

    public function view(AuthUser $authUser, Task $task): bool
    {
        return $this->canAccessTask($authUser, $task);
    }

    public function create(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'canAccessSystem') && $authUser->canAccessSystem();
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        return $this->canAccessTask($authUser, $task);
    }

    public function delete(AuthUser $authUser, Task $task): bool
    {
        return $this->canAccessTask($authUser, $task);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'isSupervisor') && $authUser->isSupervisor();
    }

    public function restore(AuthUser $authUser, Task $task): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Task $task): bool
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

    public function replicate(AuthUser $authUser, Task $task): bool
    {
        return $this->canAccessTask($authUser, $task);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }

    protected function canAccessTask(AuthUser $authUser, Task $task): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        if ($authUser->isSupervisor()) {
            return $task->user?->belongsToSameFacultadAs($authUser) ?? false;
        }

        if ($authUser->hasRole('conserje')) {
            return $task->user_id === $authUser->getKey();
        }

        return false;
    }
}
