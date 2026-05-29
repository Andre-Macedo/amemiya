<?php

declare(strict_types=1);

namespace Modules\IoT\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\IoT\Models\IoTGateway;

class IoTGatewayPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IoTGateway');
    }

    public function view(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('View:IoTGateway');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IoTGateway');
    }

    public function update(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('Update:IoTGateway');
    }

    public function delete(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('Delete:IoTGateway');
    }

    public function restore(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('Restore:IoTGateway');
    }

    public function forceDelete(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('ForceDelete:IoTGateway');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IoTGateway');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IoTGateway');
    }

    public function replicate(AuthUser $authUser, IoTGateway $ioTGateway): bool
    {
        return $authUser->can('Replicate:IoTGateway');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IoTGateway');
    }
}
