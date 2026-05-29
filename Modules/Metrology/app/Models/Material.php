<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cte',
        'category',
    ];

    protected $casts = [
        'cte' => 'decimal:2',
    ];
}
