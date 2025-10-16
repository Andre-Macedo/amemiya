<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\Calibration;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalibrationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Calibration');
    }

    public function view(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('View:Calibration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Calibration');
    }

    public function update(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('Update:Calibration');
    }

    public function delete(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('Delete:Calibration');
    }

    public function restore(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('Restore:Calibration');
    }

    public function forceDelete(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('ForceDelete:Calibration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Calibration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Calibration');
    }

    public function replicate(AuthUser $authUser, Calibration $calibration): bool
    {
        return $authUser->can('Replicate:Calibration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Calibration');
    }

}