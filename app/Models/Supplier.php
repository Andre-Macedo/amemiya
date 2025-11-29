<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
