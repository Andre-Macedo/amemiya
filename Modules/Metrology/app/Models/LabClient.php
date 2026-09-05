<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class LabClient extends Model implements AuthenticatableContract
{
    use Authenticatable, BelongsToTenant, HasApiTokens, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'cnpj',
        'email',
        'access_token',
        'password',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $hidden = [
        'password',
        'access_token',
    ];

    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class, 'lab_client_id');
    }

    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class, 'lab_client_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->access_token)) {
                $model->access_token = strtoupper(Str::random(12));
            }
        });
    }
}
