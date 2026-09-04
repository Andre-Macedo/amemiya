<?php

namespace Modules\Metrology\Observers;

use App\Models\Role;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Models\NonConformity;
use Modules\Metrology\Notifications\CriticalNCNotification;
use Modules\System\Models\User;

class NonConformityObserver
{
    /**
     * Handle the NonConformity "created" event.
     */
    public function created(NonConformity $nc): void
    {
        // Notificar administradores
        $roles = Role::whereIn('name', ['super_admin', 'admin'])->pluck('name')->toArray();
        $admins = ! empty($roles) ? User::role($roles)->get() : collect();
        if ($admins->count() > 0) {
            Notification::send($admins, new CriticalNCNotification($nc));
        }
    }

    /**
     * Handle the NonConformity "updated" event.
     */
    public function updated(NonConformity $nc): void
    {
        // Se a NC foi resolvida mas não fechada, notifica o gestor para conferir
        if ($nc->isDirty('status') && $nc->status === 'resolved') {
            $roles = Role::whereIn('name', ['super_admin', 'admin'])->pluck('name')->toArray();
            $admins = ! empty($roles) ? User::role($roles)->get() : collect();
            if ($admins->count() > 0) {
                Notification::send($admins, new CriticalNCNotification($nc));
            }
        }
    }
}
