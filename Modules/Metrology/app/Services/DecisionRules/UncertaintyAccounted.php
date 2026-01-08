<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class UncertaintyAccounted implements DecisionRuleStrategy
{
    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Rule: Passed if (Error + Uncertainty) <= Limit
        // Conservative approach
        return ($error + $uncertainty) <= $limit;
    }
}
