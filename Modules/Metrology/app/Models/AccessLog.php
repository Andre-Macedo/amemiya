<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Metrology\Database\Factories\AccessLogFactory;

/**
 * @property int $id
 * @property int $instrument_id
 * @property int $user_id
 * @property int $station_id
 * @property string $action
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AccessLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'instrument_id',
        'user_id',
        'station_id',
        'action',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
     public static function factory(): AccessLogFactory
     {
          return AccessLogFactory::new();
     }
}
