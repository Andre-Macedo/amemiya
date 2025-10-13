<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Metrology\Database\Factories\AccessLogFactory;

class AccessLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

     public static function factory(): AccessLogFactory
     {
          return AccessLogFactory::new();
     }
}
