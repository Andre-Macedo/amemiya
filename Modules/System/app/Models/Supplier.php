<?php

declare(strict_types=1);

namespace Modules\System\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Metrology\Models\InstrumentType;

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
 * @property Carbon|null $accreditation_valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Supplier extends Model
{
    use BelongsToTenant, HasFactory, HasUlids, SoftDeletes;

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
        'tenant_id',
    ];

    protected $casts = [
        'is_manufacturer' => 'boolean',
        'is_calibration_provider' => 'boolean',
        'is_maintenance_provider' => 'boolean',
        'accreditation_valid_until' => 'date',
    ];

    /**
     * @return BelongsToMany<InstrumentType, $this>
     */
    public function accreditedInstrumentTypes(): BelongsToMany
    {
        return $this->belongsToMany(InstrumentType::class, 'supplier_accreditations')
            ->withPivot(['range', 'uncertainty'])
            ->withTimestamps();
    }
}
