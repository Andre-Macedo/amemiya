<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'cte',
        'category',
    ];

    protected $casts = [
        'cte' => 'decimal:2',
    ];
}
