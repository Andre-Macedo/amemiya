<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\IntermediateCheck;

class IntermediateCheckPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IntermediateCheck');
    }

    public function view(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('View:IntermediateCheck');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IntermediateCheck');
    }

    public function update(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('Update:IntermediateCheck');
    }

    public function delete(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('Delete:IntermediateCheck');
    }

    public function restore(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('Restore:IntermediateCheck');
    }

    public function forceDelete(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('ForceDelete:IntermediateCheck');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IntermediateCheck');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IntermediateCheck');
    }

    public function replicate(AuthUser $authUser, IntermediateCheck $intermediateCheck): bool
    {
        return $authUser->can('Replicate:IntermediateCheck');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IntermediateCheck');
    }
}
