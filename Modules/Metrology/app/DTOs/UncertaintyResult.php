<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class UncertaintyResult implements \ArrayAccess
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
        public readonly float $effectiveDegreesOfFreedom = INF,
    ) {}

    public function toArray(): array
    {
        return [
            'bias' => $this->bias,
            'expanded_uncertainty' => $this->expandedUncertainty,
            'k_factor' => $this->kFactor,
            'effective_degrees_of_freedom' => is_infinite($this->effectiveDegreesOfFreedom) ? null : round($this->effectiveDegreesOfFreedom, 2),
            'uncertainty_budget' => $this->budget, // Renomeado para bater com o Frontend (Next.js)
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('UncertaintyResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('UncertaintyResult is immutable.');
    }
}
