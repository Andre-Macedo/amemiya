<?php

declare(strict_types=1);

namespace Modules\Metrology\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\System\Models\AuditLog;

trait HasAuditLogs
{
    public static function bootHasAuditLogs(): void
    {
        static::updated(function (Model $model) {

            // Ignore if only 'updated_at' changed

            if (count($model->getDirty()) === 1 && $model->isDirty('updated_at')) {
                return;
            }

            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // Filter out unchanged or ignored fields
            $oldValues = [];
            $newValues = [];

            foreach ($changes as $key => $value) {
                if ($key === 'updated_at') {
                    continue;
                }

                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $value;
            }

            if (empty($newValues)) {
                return;
            }

            AuditLog::create([
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'user_id' => Auth::id(),
                'event' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'url' => request()->fullUrl(),
            ]);
        });

        static::created(function (Model $model) {
            AuditLog::create([
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'user_id' => Auth::id(),
                'event' => 'created',
                'new_values' => $model->getAttributes(),
                'url' => request()->fullUrl(),
            ]);
        });
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
