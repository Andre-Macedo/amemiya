<?php

declare(strict_types=1);

namespace Modules\System\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property int $sequence_number
 * @property string $chain_head_hash
 * @property int $total_records
 * @property string $anchor_type
 * @property string|null $storage_path
 * @property string $manifest_hash
 * @property array<string, mixed> $manifest_payload
 * @property CarbonInterface $anchored_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class AuditChainAnchor extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'audit_chain_anchors';

    protected $fillable = [
        'tenant_id',
        'sequence_number',
        'chain_head_hash',
        'total_records',
        'anchor_type',
        'storage_path',
        'manifest_hash',
        'manifest_payload',
        'anchored_at',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'total_records' => 'integer',
        'manifest_payload' => 'array',
        'anchored_at' => 'datetime',
    ];
}
