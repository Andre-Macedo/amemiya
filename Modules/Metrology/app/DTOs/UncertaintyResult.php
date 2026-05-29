<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class UncertaintyResult
{
    /**
     * @param  float  $bias  Tendência (Erro Sistemático) calculada.
     * @param  float  $expandedUncertainty  Incerteza Expandida (k=2).
     * @param  array  $budget  Detalhamento do balanço de incertezas (fontes, valores u, coeficientes).
     * @param  float  $kFactor  Fator de abrangência utilizado (padrão 2.00).
     */
    public function __construct(
        public readonly float $bias,
        public readonly float $expandedUncertainty,
        public readonly array $budget,
        public readonly float $kFactor = 2.00,
    ) {}

    public function toArray(): array
    {
        return [
            'bias' => $this->bias,
            'expanded_uncertainty' => $this->expandedUncertainty,
            'k_factor' => $this->kFactor,
            'uncertainty_budget' => $this->budget, // Renomeado para bater com o Frontend (Next.js)
        ];
    }
}
