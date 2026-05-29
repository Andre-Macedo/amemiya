<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class GuardBand implements DecisionRuleStrategy
{
    public function __construct(private float $multiplier = 1.0) {}

    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Rule (Standard ISO 17025/ILAC-G8 Binary Decision):
        // Pass if Error <= (Limit - w * Uncertainty)
        // w is the multiplier (usually 1.0 for 95% coverage probability)

        $guardBand = $this->multiplier * $uncertainty;
        $reducedLimit = $limit - $guardBand;

        return abs($error) <= $reducedLimit;
    }
}
