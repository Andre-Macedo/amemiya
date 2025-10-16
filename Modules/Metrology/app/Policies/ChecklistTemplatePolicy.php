<?php

declare(strict_types=1);

namespace Modules\Metrology\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Metrology\Models\ChecklistTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChecklistTemplate');
    }

    public function view(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('View:ChecklistTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChecklistTemplate');
    }

    public function update(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Update:ChecklistTemplate');
    }

    public function delete(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Delete:ChecklistTemplate');
    }

    public function restore(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Restore:ChecklistTemplate');
    }

    public function forceDelete(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('ForceDelete:ChecklistTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChecklistTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChecklistTemplate');
    }

    public function replicate(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Replicate:ChecklistTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChecklistTemplate');
    }

}