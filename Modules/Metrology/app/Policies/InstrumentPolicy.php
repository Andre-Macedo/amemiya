<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\Instrument;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstrumentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Instrument');
    }

    public function view(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('View:Instrument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Instrument');
    }

    public function update(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('Update:Instrument');
    }

    public function delete(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('Delete:Instrument');
    }

    public function restore(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('Restore:Instrument');
    }

    public function forceDelete(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('ForceDelete:Instrument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Instrument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Instrument');
    }

    public function replicate(AuthUser $authUser, Instrument $instrument): bool
    {
        return $authUser->can('Replicate:Instrument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Instrument');
    }

}