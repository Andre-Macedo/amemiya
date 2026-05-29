<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Injeta automaticamente o tenant_id no log de atividade.
     */
    public function tapActivity($activity, string $eventName)
    {
        if (tenancy()->initialized) {
            $activity->tenant_id = tenancy()->tenant->id;
        }
    }
}
