<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\Auth;
use Modules\Metrology\Models\Calibration;

/**
 * Handles the approval and publishing workflow for a calibration.
 */
class ApproveCalibrationAction
{
    /**
     * Approves a calibration and marks it as published.
     *
     * Args:
     *     calibration: The calibration model instance to approve.
     *
     * Returns:
     *     The updated Calibration instance.
     *
     * Throws:
     *     \RuntimeException if the calibration is already published.
     */
    public function execute(Calibration $calibration): Calibration
    {
        if ($calibration->status === 'published') {
            throw new \RuntimeException('This calibration is already approved and published.');
        }

        $calibration->update([
            'status' => 'published',
            'approved_by_id' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Note: The 'saved' event on Calibration model triggers listeners
        // that update instrument status and due dates.

        return $calibration;
    }
}
