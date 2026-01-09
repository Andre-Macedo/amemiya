<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Models\Calibration;

class ProcessCalibrationAction
{
    public function execute(Calibration $calibration): void
    {
        $item = $calibration->calibratedItem;

        if (! $item instanceof CalibratableItem) {
            return;
        }

        $this->evaluateResult($calibration, $item);
        
        if ($calibration->isDirty('result')) {
            $calibration->saveQuietly();
        }

        $this->updateItemStatus($calibration, $item);
    }

    private function evaluateResult(Calibration $calibration, CalibratableItem $item): void
    {
        $limit = $item->getMaximumPermissibleError();

        // Evaluation only happens if MPE is defined and deviation is present
        if ($limit !== null && $limit > 0 && $calibration->deviation !== null) {
            $measuredError = abs((float) $calibration->deviation);
            $uncertainty = abs((float) $calibration->uncertainty);
            
            $strategy = $item->getDecisionRuleStrategy();
            
            $passed = $strategy->evaluate($measuredError, $uncertainty, $limit);

            if (! $passed) {
                 $calibration->result = \Modules\Metrology\Enums\CalibrationResult::Rejected;
            } else {
                if ($calibration->result !== \Modules\Metrology\Enums\CalibrationResult::ApprovedWithRestrictions) {
                    $calibration->result = \Modules\Metrology\Enums\CalibrationResult::Approved;
                }
            }
        }
    }

    private function updateItemStatus(Calibration $calibration, CalibratableItem $item): void
    {
        if ($calibration->result) {
            $item->processCalibrationResult($calibration, $calibration->result);
        }
    }
}
