<?php

namespace Modules\System\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\IoT\Models\IoTNode;

class Machine extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'name',
        'code',
        'description',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Uma máquina pertence a um Posto de Trabalho (Bancada).
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Sensores IoT fixados nesta máquina.
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(IoTNode::class, 'machine_id');
    }

    /**
     * Grupo normativo ISO 20816 (ex: group_1 ou group_2).
     */
    public function getIsoGroup(): string
    {
        return (string) ($this->metadata['iso_group'] ?? 'group_2');
    }

    /**
     * Tipo de fundação mecânica (rigid ou flexible).
     */
    public function getFoundationType(): string
    {
        return (string) ($this->metadata['foundation_type'] ?? 'rigid');
    }
}
