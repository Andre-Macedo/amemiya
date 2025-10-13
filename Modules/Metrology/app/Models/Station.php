<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Metrology\Database\Factories\StationFactory;

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
