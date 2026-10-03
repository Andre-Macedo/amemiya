<?php

declare(strict_types=1);

namespace Modules\Metrology\Services\DecisionRules;

class UncertaintyAccounted implements DecisionRuleStrategy
{
    public function evaluate(float $error, float $uncertainty, float $limit): bool
    {
        // Regra ILAC-G8:09/2019 com Incerteza Contabilizada (Zona de Aceitação Estrita):
        // Conforme se (|Erro| + U) <= Limite (MPE).
        return (abs($error) + abs($uncertainty)) <= abs($limit);
    }
}
