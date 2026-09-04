<?php

namespace App\Traits;

use App\Models\Tenant;
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
            } elseif (app()->environment('testing') && ! $model->getAttribute('tenant_id')) {
                $tenant = Tenant::first() ?? Tenant::create([
                    'name' => 'Testing Tenant',
                    'slug' => 'testing',
                ]);
                tenancy()->initialize($tenant);
                $model->setAttribute('tenant_id', $tenant->id);
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenancy()->initialized) {
                $builder->where($builder->getModel()->qualifyColumn('tenant_id'), tenancy()->tenant->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'), 'tenant_id');
    }
}
