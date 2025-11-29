<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Database\Factories\StationFactory;
use Modules\Metrology\Models\Instrument;

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
