<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Metrology\Database\Factories\ChecklistFactory;

class Checklist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['calibration_id', 'steps', 'completed'];

    protected $casts = [
        'steps' => 'array',
        'completed' => 'boolean',
    ];

    public function calibration()
    {
        return $this->belongsTo(Calibration::class);
    }

    // protected static function newFactory(): ChecklistFactory
    // {
    //     // return ChecklistFactory::new();
    // }
}
