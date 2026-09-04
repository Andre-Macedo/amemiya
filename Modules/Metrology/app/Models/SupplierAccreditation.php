<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SupplierAccreditation extends Pivot
{
    use BelongsToTenant, HasUlids;

    protected $table = 'supplier_accreditations';

    public $incrementing = false;

    protected $keyType = 'string';
}
