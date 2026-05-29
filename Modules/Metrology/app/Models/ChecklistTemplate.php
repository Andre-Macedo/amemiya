<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Metrology\Database\Factories\ChecklistTemplateFactory;

/**
 * @property string $id
 * @property string $name
 * @property int $version
 * @property bool $is_active
 */
class ChecklistTemplate extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'version',
        'is_active',
        'instrument_type_id',
        'parent_version_id',
        'revision_notes',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class)->orderBy('order');
    }

    public function instrumentType(): BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'parent_version_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class, 'parent_version_id');
    }

    /**
     * Cria uma nova revisão deste template.
     * Desativa a versão atual e gera uma cópia idêntica incrementando a versão.
     */
    public function createRevision(?string $notes = null): self
    {
        return DB::transaction(function () use ($notes) {
            // 1. Clona o cabeçalho
            $newVersion = $this->replicate(['id', 'published_at']);
            $newVersion->version = $this->version + 1;
            $newVersion->is_active = true;
            $newVersion->parent_version_id = $this->id;
            $newVersion->revision_notes = $notes;
            $newVersion->save();

            // 2. Clona os itens
            foreach ($this->items as $item) {
                $newItem = $item->replicate(['id']);
                $newItem->checklist_template_id = $newVersion->id;
                $newItem->save();
            }

            // 3. Desativa a versão antiga
            $this->update(['is_active' => false]);

            return $newVersion;
        });
    }

    public static function factory(): ChecklistTemplateFactory
    {
        return ChecklistTemplateFactory::new();
    }
}
