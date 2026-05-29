<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\System\Models\User;

class Attachment extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'attachable_id',
        'attachable_type',
        'file_name',
        'original_name',
        'mime_type',
        'size',
        'file_path',
        'disk',
        'uploaded_by',
    ];

    /**
     * Get the parent attachable model (Instrument, Supplier, etc).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
