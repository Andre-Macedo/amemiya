<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\InstrumentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstrumentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InstrumentType');
    }

    public function view(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('View:InstrumentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InstrumentType');
    }

    public function update(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('Update:InstrumentType');
    }

    public function delete(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('Delete:InstrumentType');
    }

    public function restore(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('Restore:InstrumentType');
    }

    public function forceDelete(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('ForceDelete:InstrumentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InstrumentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InstrumentType');
    }

    public function replicate(AuthUser $authUser, InstrumentType $instrumentType): bool
    {
        return $authUser->can('Replicate:InstrumentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InstrumentType');
    }

}