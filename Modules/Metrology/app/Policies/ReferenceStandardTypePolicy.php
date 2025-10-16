<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\ReferenceStandardType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReferenceStandardTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReferenceStandardType');
    }

    public function view(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('View:ReferenceStandardType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReferenceStandardType');
    }

    public function update(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('Update:ReferenceStandardType');
    }

    public function delete(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('Delete:ReferenceStandardType');
    }

    public function restore(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('Restore:ReferenceStandardType');
    }

    public function forceDelete(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('ForceDelete:ReferenceStandardType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReferenceStandardType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReferenceStandardType');
    }

    public function replicate(AuthUser $authUser, ReferenceStandardType $referenceStandardType): bool
    {
        return $authUser->can('Replicate:ReferenceStandardType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReferenceStandardType');
    }

}