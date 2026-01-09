<?php

declare(strict_types=1);

namespace Modules\Metrology\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;

interface CalibratableItem
{
    public function getCalibrationFrequencyMonths(): ?int;

    public function getMaximumPermissibleError(): ?float;

    public function getDecisionRuleStrategy(): DecisionRuleStrategy;

    public function processCalibrationResult(Calibration $calibration, \Modules\Metrology\Enums\CalibrationResult $status): void;

    public function calibrations(): MorphMany;
}
