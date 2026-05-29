<?php

namespace Modules\System\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'type',
        'location',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Localização Pai (ex: Departamento pai desta bancada)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'parent_id');
    }

    /**
     * Localizações Filhas (ex: Bancadas deste departamento)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Station::class, 'parent_id');
    }

    /**
     * Retorna o caminho completo da localização (ex: Planta A > Depto Qualidade > Bancada 1)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current->name);
        }

        return implode(' > ', $path);
    }
}
