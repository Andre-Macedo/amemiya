<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $trade_name
 * @property string|null $cnpj
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_manufacturer
 * @property bool $is_calibration_provider
 * @property bool $is_maintenance_provider
 * @property string|null $rbc_code
 * @property \Illuminate\Support\Carbon|null $accreditation_valid_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'trade_name',
        'cnpj',
        'email',
        'phone',
        'is_manufacturer',
        'is_calibration_provider',
        'is_maintenance_provider',
        'rbc_code',
        'accreditation_valid_until',
    ];

    protected $casts = [
        'is_manufacturer' => 'boolean',
        'is_calibration_provider' => 'boolean',
        'is_maintenance_provider' => 'boolean',
        'accreditation_valid_until' => 'date',
    ];
}
