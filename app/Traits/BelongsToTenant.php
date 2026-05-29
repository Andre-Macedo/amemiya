<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (tenancy()->initialized && ! $model->getAttribute('tenant_id')) {
                $model->setAttribute('tenant_id', tenancy()->tenant->id);
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenancy()->initialized) {
                $builder->where('tenant_id', tenancy()->tenant->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'), 'tenant_id');
    }
}
