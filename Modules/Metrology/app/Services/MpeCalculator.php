<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Exceptions\MpeNotResolvableException;

class MpeCalculator
{
    /**
     * Resolve o MPE como valor absoluto para um determinado ponto de medição.
     *
     * @throws MpeNotResolvableException quando o MPE é percentual/ppm e o valor nominal não é fornecido.
     */
    public static function resolve(CalibratableItem $item, ?float $nominalValue = null): ?float
    {
        $rawMpe = $item->mpe ?? null;
        $mpeType = $item->mpe_type ?? null;
        $mpeValue = $item->mpe_value ?? null;

        // Se mpe_type não estiver definido, infere a partir do campo textual mpe
        if ($mpeType === null && is_string($rawMpe) && str_contains($rawMpe, '%')) {
            $mpeType = 'percentage';
        }

        $mpeType = $mpeType ?? 'absolute';

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
