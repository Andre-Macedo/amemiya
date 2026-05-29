<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

/**
 * Serviço responsável por correções ambientais em medições dimensionais.
 * Segue os princípios da ISO/TR 16015 e GUM.
 */
class ThermalCorrectionService
{
    /**
     * Temperatura de referência padrão (ISO 1).
     */
    public const REFERENCE_TEMP = 20.0;

    /**
     * Coeficientes de Expansão Térmica (CTE) comuns (x 10^-6 / °C).
     */
    public const MATERIALS = [
        'steel' => 11.5,
        'stainless_steel' => 16.0,
        'carbide' => 5.0, // Carbeto de Tungstênio
        'ceramic' => 9.5,
        'aluminum' => 23.0,
        'brass' => 19.0,
        'glass' => 8.0,
        'granite' => 6.5,
    ];

    /**
     * Calcula o comprimento corrigido a 20°C.
     *
     * Fórmula: L20 = L * [1 - alpha * (T - 20)]
     *
     * @param float $measuredLength O valor lido (em mm, polegadas, etc).
     * @param float $temperature A temperatura da peça no momento da medição (°C).
     * @param float $cte O coeficiente de expansão térmica do material (em 10^-6/°C). Ex: Aço = 11.5
     * @return float O valor corrigido.
     */
    public function correctLength(float $measuredLength, float $temperature, float $cte): float
    {
        $deltaT = $temperature - self::REFERENCE_TEMP;

        // CTE entra como micrometros, então dividimos por 1 milhão
        $alpha = $cte / 1_000_000;

        // Fator de correção: 1 - (alpha * deltaT)
        // Nota: Se a peça está mais quente que 20°C, ela está expandida.
        // O valor a 20°C seria MENOR. Por isso o sinal negativo na fórmula simplificada de redução.
        // L20 = L_measured / (1 + alpha * deltaT) é a exata, mas a aproximação linear L * (1 - alpha * deltaT) é aceita para pequenos deltas.

        return $measuredLength * (1 - ($alpha * $deltaT));
    }

    /**
     * Calcula a incerteza introduzida pela correção térmica.
     *
     * Fontes:
     * 1. Incerteza da medição de temperatura (uT).
     * 2. Incerteza do coeficiente de expansão (uAlpha).
     *
     * @param float $length Comprimento nominal.
     * @param float $temperature Temperatura média.
     * @param float $uTemperature Incerteza padrão da temperatura (k=1).
     * @param float $cte Coeficiente de expansão.
     * @param float $uCte Incerteza do coeficiente (geralmente 10% do CTE se desconhecido).
     * @return float A incerteza padrão combinada (u_c) da correção.
     */
    public function calculateCorrectionUncertainty(
        float $length,
        float $temperature,
        float $uTemperature,
        float $cte,
        float $uCte
    ): float {
        $deltaT = abs($temperature - self::REFERENCE_TEMP);
        $alpha = $cte / 1_000_000;
        $uAlpha = $uCte / 1_000_000;

        // Sensibilidade à temperatura: c_T = -L * alpha
        $c_T = $length * $alpha;

        // Sensibilidade ao CTE: c_alpha = -L * deltaT
        $c_alpha = $length * $deltaT;

        // Soma quadrática: u_corr = sqrt( (c_T * uT)^2 + (c_alpha * uAlpha)^2 )
        return sqrt(
            pow($c_T * $uTemperature, 2) +
            pow($c_alpha * $uAlpha, 2)
        );
    }
}
