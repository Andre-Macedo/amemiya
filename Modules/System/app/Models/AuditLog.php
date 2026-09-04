<?php

declare(strict_types=1);

namespace Modules\System\Models;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Adapter model for audit logs mapping to Spatie Activitylog.
 */
class AuditLog extends Activity
{
    protected $table = 'activity_log';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (isset($model->attributes['auditable_type'])) {
                $model->subject_type = $model->attributes['auditable_type'];
                unset($model->attributes['auditable_type']);
            }
            if (isset($model->attributes['auditable_id'])) {
                $model->subject_id = $model->attributes['auditable_id'];
                unset($model->attributes['auditable_id']);
            }
            if (isset($model->attributes['user_id'])) {
                $model->causer_id = $model->attributes['user_id'];
                $model->causer_type = User::class;
                unset($model->attributes['user_id']);
            }
            if (isset($model->attributes['new_values']) || isset($model->attributes['old_values'])) {
                $properties = is_array($model->properties) ? $model->properties : ($model->properties?->toArray() ?? []);
                if (isset($model->attributes['new_values'])) {
                    $properties['attributes'] = $model->attributes['new_values'];
                    unset($model->attributes['new_values']);
                }
                if (isset($model->attributes['old_values'])) {
                    $properties['old'] = $model->attributes['old_values'];
                    unset($model->attributes['old_values']);
                }
                $model->properties = $properties;
            }
        });
    }

    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column)) {
                    $column = match ($column) {
                        'auditable_type' => 'subject_type',
                        'auditable_id' => 'subject_id',
                        'user_id' => 'causer_id',
                        default => $column,
                    };
                }

                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    public function user()
    {
        return $this->causer();
    }

    public function auditable(): MorphTo
    {
        return $this->subject();
    }

    public function getAuditableTypeAttribute(): ?string
    {
        return $this->subject_type;
    }

    public function setAuditableTypeAttribute(?string $value): void
    {
        $this->subject_type = $value;
    }

    public function getAuditableIdAttribute(): ?string
    {
        return $this->subject_id !== null ? (string) $this->subject_id : null;
    }

    public function setAuditableIdAttribute(?string $value): void
    {
        $this->subject_id = $value;
    }

    public function getUserIdAttribute(): ?string
    {
        return $this->causer_id !== null ? (string) $this->causer_id : null;
    }

    public function setUserIdAttribute(?string $value): void
    {
        $this->causer_id = $value;
    }

    public function getNewValuesAttribute(): ?array
    {
        $props = is_array($this->properties) ? $this->properties : ($this->properties?->toArray() ?? []);

        return $props['attributes'] ?? null;
    }

    public function getOldValuesAttribute(): ?array
    {
        $props = is_array($this->properties) ? $this->properties : ($this->properties?->toArray() ?? []);

        return $props['old'] ?? null;
    }
}
