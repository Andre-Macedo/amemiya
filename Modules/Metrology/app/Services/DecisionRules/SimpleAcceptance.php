<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class SimpleAcceptance implements DecisionRuleStrategy
{
    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Regra ILAC-G8:09/2019 Aceitação Simples (Shared Risk):
        // Conforme se |Erro| <= Limite (MPE). Incerteza é desconsiderada na fronteira.
        return abs($error) <= abs($limit);
    }
}
