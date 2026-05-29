<?php

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LabClient extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->access_token)) {
                $model->access_token = strtoupper(Str::random(12));
            }
        });
    }
}
