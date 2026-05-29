<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;

/**
 * Handles the rejection of a calibration during the review process.
 */
class RejectCalibrationAction
{
    /**
     * Rejects a calibration and returns it for correction.
     *
     * Args:
     *     calibration: The calibration model instance to reject.
     *
     * Returns:
     *     The updated Calibration instance.
     */
    public function execute(Calibration $calibration): Calibration
    {
        $calibration->update([
            'status' => 'rejected_review', // Technical rejection in review process
        ]);

        return $calibration;
    }
}
