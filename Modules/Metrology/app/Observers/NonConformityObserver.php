<?php

namespace Modules\Metrology\Observers;

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
        $admins = User::role(['super_admin', 'admin'])->get();
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
            $admins = User::role(['super_admin', 'admin'])->get();
            if ($admins->count() > 0) {
                Notification::send($admins, new CriticalNCNotification($nc));
            }
        }
    }
}
