<?php

declare(strict_types=1);

namespace Modules\Metrology\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;

/**
 * Interface para entidades que podem ser calibradas (Instrumentos, Padrões).
 */
interface CalibratableItem
{
    /**
     * Retorna a frequência de calibração em meses.
     */
    public function getCalibrationFrequencyMonths(): ?int;

    public function getMaximumPermissibleError(): ?float;

    public function getDecisionRuleStrategy(): DecisionRuleStrategy;

    public function processCalibrationResult(Calibration $calibration, CalibrationResult $status): void;

    public function calibrations(): MorphMany;
}
