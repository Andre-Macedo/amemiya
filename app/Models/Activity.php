<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\System\Services\AuditChainService;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasUlids;

    protected $casts = [
        'properties' => 'collection',
        'sequence_number' => 'integer',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Activity $activity): void {
            app(AuditChainService::class)->chainRecord($activity);
        });
    }
}
