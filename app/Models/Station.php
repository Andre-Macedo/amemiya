<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Database\Factories\StationFactory;
use Modules\Metrology\Models\Instrument;

/**
 * @property int $id
 * @property string $name
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Metrology\Models\Instrument> $instruments
 */
class Station extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'location'];

    public function instruments()
    {
        return $this->hasMany(Instrument::class, 'current_station_id');
    }

     public static function factory(): StationFactory
     {
          return StationFactory::new();
     }
}
