<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\NonConformity;

class NonConformityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NonConformity');
    }

    public function view(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('View:NonConformity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NonConformity');
    }

    public function update(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('Update:NonConformity');
    }

    public function delete(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('Delete:NonConformity');
    }

    public function restore(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('Restore:NonConformity');
    }

    public function forceDelete(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('ForceDelete:NonConformity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NonConformity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NonConformity');
    }

    public function replicate(AuthUser $authUser, NonConformity $nonConformity): bool
    {
        return $authUser->can('Replicate:NonConformity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NonConformity');
    }
}
