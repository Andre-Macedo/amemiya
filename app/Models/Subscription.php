<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'name',
        'gateway',
        'gateway_id',
        'gateway_status',
        'status',
        'trial_ends_at',
        'ends_at',
        'next_billing_at',
        'last_payment_at',
        'canceled_at',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Determina se a assinatura está ativa ou em período de teste.
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial();
    }

    /**
     * Determina se a assinatura está ativa.
     */
    public function active(): bool
    {
        return $this->status === 'active' &&
               (is_null($this->ends_at) || $this->ends_at->isFuture());
    }

    /**
     * Determina se a assinatura está em período de teste.
     */
    public function onTrial(): bool
    {
        return $this->status === 'trialing' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }
}
