<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains, HasUlids;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'plan_id',
            'name',
            'slug',
            'status',
            'trial_ends_at',
            'subscription_ends_at',
            'contact_email',
            'contact_phone',
            'address',
            'limit_instruments_override',
            'limit_users_override',
        ];
    }

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'limit_instruments_override' => 'integer',
        'limit_users_override' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    /**
     * Verifica se um módulo específico está ativo para este tenant.
     */
    public function hasModule(string $moduleId): bool
    {
        // Módulos marcados como 'core' no config são sempre acessíveis
        if (config("amemiya.modules.{$moduleId}.is_core", false)) {
            return true;
        }

        return $this->activeModules()
            ->where('module_id', $moduleId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Obtém a assinatura principal ativa.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('name', 'default')
            ->whereIn('status', ['active', 'trialing'])
            ->latestOfMany();
    }

    /**
     * Verifica se o tenant tem acesso operacional.
     */
    public function hasAccess(): bool
    {
        $sub = $this->activeSubscription;

        return $sub && $sub->valid();
    }
}
