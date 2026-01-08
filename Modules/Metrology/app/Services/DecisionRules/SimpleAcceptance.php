<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class SimpleAcceptance implements DecisionRuleStrategy
{
    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Rule: Passed if Error <= Limit
        // Uncertainty is ignored (Shared Risk)
        return $error <= $limit;
    }
}
