<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
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
        return method_exists($authUser, 'isSupervisor') && $authUser->isSupervisor();
    }

    public function view(AuthUser $authUser, User $user): bool
    {
        return method_exists($authUser, 'canManageUser') && $authUser->canManageUser($user);
    }

    public function create(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'isSupervisor') && $authUser->isSupervisor();
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        return method_exists($authUser, 'canManageUser') && $authUser->canManageUser($user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        return method_exists($authUser, 'canManageUser') && $authUser->canManageUser($user);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
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

    public function replicate(AuthUser $authUser, User $user): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
