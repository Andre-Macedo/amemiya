<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

interface DecisionRuleStrategy
{
    /**
     * Determine if a measurement passes based on the rule.
     *
     * @param float $error (Deviation) - The measured bias (abs value usually passed, or logic handles it)
     * @param float $uncertainty - The uncertainty of the measurement
     * @param float $limit - The maximum permissible error (MPE)
     * @return bool - True if Passed, False if Failed
     */
    public function evaluate(float $error, float $uncertainty, float $limit): bool;
}
