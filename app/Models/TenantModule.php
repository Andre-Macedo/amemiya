<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'module_id',
        'activated_at',
        'expires_at',
        'settings',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'settings' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obtém os dados do módulo a partir da configuração central.
     */
    public function getConfig(): array
    {
        return config("amemiya.modules.{$this->module_id}", []);
    }
}
