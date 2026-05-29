<?php

declare(strict_types=1);

namespace Modules\IoT\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\IoT\Models\IoTNode;

class IoTNodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IoTNode');
    }

    public function view(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('View:IoTNode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IoTNode');
    }

    public function update(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('Update:IoTNode');
    }

    public function delete(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('Delete:IoTNode');
    }

    public function restore(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('Restore:IoTNode');
    }

    public function forceDelete(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('ForceDelete:IoTNode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IoTNode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IoTNode');
    }

    public function replicate(AuthUser $authUser, IoTNode $ioTNode): bool
    {
        return $authUser->can('Replicate:IoTNode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IoTNode');
    }
}
