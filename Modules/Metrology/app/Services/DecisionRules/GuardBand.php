<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class GuardBand implements DecisionRuleStrategy
{
    public function __construct(private float $multiplier = 1.0) {}

    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Regra ILAC-G8:09/2019 Banda de Guarda (Guard Banding):
        // Conforme se |Erro| <= Limite - w * U
        // w é o multiplicador da faixa de proteção (tipicamente 1.0 para PFA <= 2.5% ou r = 1.0)
        $guardBand = abs($this->multiplier) * abs($uncertainty);
        $reducedLimit = abs($limit) - $guardBand;

        if ($reducedLimit < 0) {
            return false;
        }

        return abs($error) <= $reducedLimit;
    }
}
