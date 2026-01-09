<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\MetrologyException;

class CalibrationValidator
{
    /**
     * Determine if the item can be calibrated.
     *
     * @param CalibratableItem $item
     * @return bool
     * @throws MetrologyException
     */
    public function canBeCalibrated(CalibratableItem $item): bool
    {
        // Enforce strong typing on status by checking if it's an Enum or string
        $status = $item->status;

        // If models are not ensuring casting yet (hybrid state), handle strings strictly
        if (is_string($status)) {
            // Should strictly use Enums now per Phase 5, but safety check
            if (in_array($status, ['rejected', 'lost'])) {
                throw new MetrologyException("Item status '{$status}' prevents calibration without maintenance.");
            }
            return true;
        }

        // Enum check
        if ($status instanceof ItemStatus) {
            if (in_array($status, [ItemStatus::Rejected, ItemStatus::Lost])) {
                throw new MetrologyException("Item status '{$status->getLabel()}' prevents calibration without maintenance.");
            }
        }

        return true;
    }
}
