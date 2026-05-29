<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class MeasurementCalculationData
{
    /**
     * @param  array<float>  $readings  Lista de leituras obtidas no instrumento.
     * @param  float  $resolution  Resolução do instrumento calibrado (menor divisão).
     * @param  float  $standardActualValue  Valor verdadeiro convencional do padrão utilizado (VME).
     * @param  float  $standardUncertainty  Incerteza do padrão (expandida, do certificado).
     * @param  float  $standardK  Fator de abrangência do padrão (geralmente 2.00).
     * @param  float|null  $temperature  Temperatura no momento da medição (°C). Null se não informado.
     * @param  float  $cte  Coeficiente de Expansão Térmica (padrão 11.5 para Aço).
     */
    public function __construct(
        public readonly array $readings,
        public readonly float $resolution,
        public readonly float $standardActualValue,
        public readonly float $standardUncertainty = 0.0,
        public readonly float $standardK = 2.00,
        public readonly ?float $temperature = null,
        public readonly float $cte = 11.5, // Aço padrão
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            readings: $data['readings'] ?? [],
            resolution: (float) ($data['resolution'] ?? 0.0),
            standardActualValue: (float) ($data['standard_actual_value'] ?? 0.0),
            standardUncertainty: (float) ($data['standard_uncertainty'] ?? 0.0),
            standardK: (float) ($data['standard_k'] ?? 2.00),
            temperature: isset($data['temperature']) ? (float) $data['temperature'] : null,
            cte: (float) ($data['cte'] ?? 11.5),
        );
    }
}
