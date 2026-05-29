<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'max_instruments',
        'max_users',
        'max_storage_mb',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_instruments' => 'integer',
        'max_users' => 'integer',
        'max_storage_mb' => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
