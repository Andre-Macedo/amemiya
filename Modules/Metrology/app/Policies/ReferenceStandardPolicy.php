<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\ReferenceStandard;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReferenceStandardPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReferenceStandard');
    }

    public function view(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('View:ReferenceStandard');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReferenceStandard');
    }

    public function update(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('Update:ReferenceStandard');
    }

    public function delete(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('Delete:ReferenceStandard');
    }

    public function restore(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('Restore:ReferenceStandard');
    }

    public function forceDelete(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('ForceDelete:ReferenceStandard');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReferenceStandard');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReferenceStandard');
    }

    public function replicate(AuthUser $authUser, ReferenceStandard $referenceStandard): bool
    {
        return $authUser->can('Replicate:ReferenceStandard');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReferenceStandard');
    }

}