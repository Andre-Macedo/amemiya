<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class GuardBand implements DecisionRuleStrategy
{
    // Usually Guard Band multiplier (w) is 1.0 (1x Uncertainty)
    // Could be configurable, but assuming 1U for now.
    private float $multiplier = 1.0;

    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Rule: Passed if Error <= (Limit - w * Uncertainty)
        // Strictly reducing the limit
        
        $guardBand = $this->multiplier * $uncertainty;
        $reducedLimit = $limit - $guardBand;

        return $error <= $reducedLimit;
    }
}
