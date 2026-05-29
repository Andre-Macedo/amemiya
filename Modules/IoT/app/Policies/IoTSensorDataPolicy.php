<?php

declare(strict_types=1);

namespace Modules\IoT\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\IoT\Models\IoTSensorData;

class IoTSensorDataPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IoTSensorData');
    }

    public function view(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('View:IoTSensorData');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IoTSensorData');
    }

    public function update(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('Update:IoTSensorData');
    }

    public function delete(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('Delete:IoTSensorData');
    }

    public function restore(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('Restore:IoTSensorData');
    }

    public function forceDelete(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('ForceDelete:IoTSensorData');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IoTSensorData');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IoTSensorData');
    }

    public function replicate(AuthUser $authUser, IoTSensorData $ioTSensorData): bool
    {
        return $authUser->can('Replicate:IoTSensorData');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IoTSensorData');
    }
}
