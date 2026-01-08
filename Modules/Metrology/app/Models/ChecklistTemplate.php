<?php
declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\ChecklistTemplateFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $instrument_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ChecklistTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Os atributos que podem ser atribuídos em massa.
     */
    protected $fillable = ['name', 'instrument_type_id'];

    public function checklists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class);
    }

    public function instrumentType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

     public static function factory(): ChecklistTemplateFactory
     {
          return ChecklistTemplateFactory::new();
     }
}
