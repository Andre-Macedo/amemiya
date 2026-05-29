<?php

declare(strict_types=1);

namespace Modules\Metrology\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Metrology\Models\Attachment;

/**
 * Trait para permitir que um Model possua anexos.
 */
trait HasAttachments
{
    /**
     * @return MorphMany<Attachment>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
