<?php

declare(strict_types=1);

namespace Modules\Metrology\Traits;

trait HasAssetIdentity
{
    public function getEffectiveSerialNumberAttribute(): string
    {
        if (! empty($this->serial_number)) {
            return $this->serial_number;
        }

        if (method_exists($this, 'parent') && $this->parent_id && $this->parent) {
            return $this->parent->serial_number.' (Kit)';
        }

        return 'S/N';
    }

    public function getEffectiveStockNumberAttribute(): string
    {
        if (! empty($this->stock_number)) {
            return $this->stock_number;
        }

        if (method_exists($this, 'parent') && $this->parent_id && $this->parent) {
            return $this->parent->stock_number.' (Kit)';
        }

        return 'N/A';
    }
}
