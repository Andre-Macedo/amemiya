<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Exceptions\MpeNotResolvableException;

class MpeCalculator
{
    /**
     * Resolve o MPE como valor absoluto para um determinado ponto de medição.
     * Suporta modelos absolutos, percentuais, ppm e fórmulas compostas/mistas (Benchmark Fluke/Beamex: ±(a% rdg + b% FS + c d)).
     *
     * @throws MpeNotResolvableException quando o MPE é percentual/ppm/composto e parâmetros necessários não são fornecidos.
     */
    public static function resolve(
        CalibratableItem $item,
        ?float $nominalValue = null,
        ?float $span = null,
        ?float $resolution = null
    ): ?float {
        $rawMpe = $item->mpe ?? null;
        $mpeType = $item->mpe_type ?? null;
        $mpeValue = $item->mpe_value ?? null;

        $span = $span ?? self::extractSpan($item->measuring_range ?? null);
        $resolution = $resolution ?? self::extractResolution($item->resolution ?? null);

        // Infere mpe_type se não estiver definido explicitamente
        if ($mpeType === null) {
            if (is_string($rawMpe) && str_contains($rawMpe, '+')) {
                $mpeType = 'compound';
            } elseif (is_string($rawMpe) && str_contains($rawMpe, '%')) {
                $mpeType = 'percentage';
            } elseif (is_string($rawMpe) && stripos($rawMpe, 'ppm') !== false) {
                $mpeType = 'ppm';
            }
        }

        // Se o texto mpe contiver '+', trata sempre como fórmula composta
        if (is_string($rawMpe) && str_contains($rawMpe, '+')) {
            $mpeType = 'compound';
        }

        $mpeType = $mpeType ?? 'absolute';

        if (in_array($mpeType, ['compound', 'composite', 'mixed'], true)) {
            if (is_string($rawMpe) && trim($rawMpe) !== '') {
                return self::resolveCompound($rawMpe, $nominalValue, $span, $resolution);
            }

            if ($mpeValue !== null) {
                return (float) $mpeValue;
            }

            return 0.0;
        }

        if ($mpeValue !== null) {
            $resolvedNumeric = (float) $mpeValue;
        } elseif ($rawMpe !== null) {
            $normalized = str_replace(',', '.', (string) $rawMpe);
            $cleanNumeric = preg_replace('/[^0-9.]/', '', $normalized);
            $resolvedNumeric = is_numeric($cleanNumeric) ? (float) $cleanNumeric : null;
        } else {
            $resolvedNumeric = null;
        }

        if ($resolvedNumeric === null || $resolvedNumeric <= 0.0) {
            return 0.0;
        }

        return match ($mpeType) {
            'percentage' => self::resolvePercentage($resolvedNumeric, $nominalValue),
            'ppm' => self::resolvePpm($resolvedNumeric, $nominalValue),
            default => $resolvedNumeric,
        };
    }

    /**
     * Resolve fórmula composta de tolerância (Benchmark Fluke/Beamex):
     * Ex: "±(0.05% rdg + 0.02% FS + 2d)", "0.05% + 0.01", "0.1% rdg + 0.05% FS", "50 ppm + 0.005".
     *
     * @throws MpeNotResolvableException
     */
    public static function resolveCompound(
        string $formula,
        ?float $nominalValue,
        ?float $span = null,
        ?float $resolution = null
    ): float {
        $cleaned = str_ireplace(['±', '+/-', '(', ')', '[', ']'], '', $formula);
        $cleaned = str_replace(',', '.', $cleaned);

        $terms = explode('+', $cleaned);
        $total = 0.0;

        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }

            // 1. Termo de Escala / Fundo de Escala / Span (ex: 0.02% FS, 0.05% span, 0.01% escala)
            if (preg_match('/(fs|full\s*scale|fundo\s*de\s*escala|\bfe\b|span|escala|range)/i', $term)) {
                if ($span === null) {
                    throw new MpeNotResolvableException(
                        'MPE composto com termo de fundo de escala (% FS/span) requer a capacidade ou faixa de medição (span) do instrumento.'
                    );
                }

                if (str_contains($term, '%')) {
                    if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $term, $m)) {
                        $total += ((float) $m[1] / 100.0) * $span;
                    }
                } elseif (stripos($term, 'ppm') !== false) {
                    if (preg_match('/(\d+(?:\.\d+)?)\s*ppm/i', $term, $m)) {
                        $total += ((float) $m[1] / 1_000_000.0) * $span;
                    }
                } else {
                    if (preg_match('/(\d+(?:\.\d+)?)/', $term, $m)) {
                        $total += (float) $m[1] * $span;
                    }
                }

                continue;
            }

            // 2. Termo de Dígitos / Resolução / Contagens (ex: 2d, 3 digits, 1 contagem)
            if (preg_match('/(\d+(?:\.\d+)?)\s*(d|digits?|d[ií]gitos?|counts?|contagens?|divis(?:ao|oes|[ãõ]es)?)\b/i', $term, $m)) {
                if ($resolution === null) {
                    throw new MpeNotResolvableException(
                        'MPE composto com termo de contagens/dígitos (d) requer a resolução do instrumento.'
                    );
                }

                $digits = (float) $m[1];
                $total += $digits * $resolution;

                continue;
            }

            // 3. Termo de Leitura / Ponto Nominal (ex: 0.05% rdg, 0.05%, 50 ppm rdg)
            if (str_contains($term, '%') || stripos($term, 'ppm') !== false || preg_match('/\b(rdg|reading|leitura)\b/i', $term)) {
                if ($nominalValue === null) {
                    throw new MpeNotResolvableException(
                        'MPE composto com termo de leitura (% rdg) requer o valor nominal do ponto de medição.'
                    );
                }

                if (str_contains($term, '%')) {
                    if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $term, $m)) {
                        $total += ((float) $m[1] / 100.0) * abs($nominalValue);
                    }
                } elseif (stripos($term, 'ppm') !== false) {
                    if (preg_match('/(\d+(?:\.\d+)?)\s*ppm/i', $term, $m)) {
                        $total += ((float) $m[1] / 1_000_000.0) * abs($nominalValue);
                    }
                }

                continue;
            }

            // 4. Termo Constante / Offset Absoluto (ex: 0.01, 0.005 mm)
            if (preg_match('/(\d+(?:\.\d+)?)/', $term, $m)) {
                $total += (float) $m[1];
            }
        }

        return round($total, 6);
    }

    /**
     * Extrai a amplitude / span de uma string de faixa de medição (ex: "0-150mm", "25-50", "-20 a 100 °C").
     */
    public static function extractSpan(?string $rangeString): ?float
    {
        if ($rangeString === null || trim($rangeString) === '') {
            return null;
        }

        $cleaned = str_replace(',', '.', $rangeString);

        if (preg_match('/([+-]?\d+(?:\.\d+)?)\s*(?:-|to|a|\.\.)\s*([+-]?\d+(?:\.\d+)?)/i', $cleaned, $matches)) {
            $min = (float) $matches[1];
            $max = (float) $matches[2];

            return abs($max - $min);
        }

        if (preg_match('/([+-]?\d+(?:\.\d+)?)/', $cleaned, $matches)) {
            return abs((float) $matches[1]);
        }

        return null;
    }

    /**
     * Extrai a resolução numérica como float.
     */
    public static function extractResolution(mixed $resolution): ?float
    {
        if ($resolution === null || $resolution === '') {
            return null;
        }

        if (is_numeric($resolution)) {
            return (float) $resolution;
        }

        if (is_string($resolution)) {
            $cleaned = str_replace(',', '.', $resolution);
            if (preg_match('/(\d+(?:\.\d+)?)/', $cleaned, $matches)) {
                return (float) $matches[1];
            }
        }

        return null;
    }

    private static function resolvePercentage(float $percentage, ?float $nominalValue): float
    {
        if ($nominalValue === null) {
            throw new MpeNotResolvableException(
                'MPE percentual requer o valor nominal do ponto de medição para cálculo de tolerância absoluta.'
            );
        }

        return round(($percentage / 100.0) * abs($nominalValue), 6);
    }

    private static function resolvePpm(float $ppm, ?float $nominalValue): float
    {
        if ($nominalValue === null) {
            throw new MpeNotResolvableException(
                'MPE do tipo ppm requer o valor nominal do ponto de medição para cálculo de tolerância absoluta.'
            );
        }

        return round(($ppm / 1_000_000.0) * abs($nominalValue), 6);
    }
}
