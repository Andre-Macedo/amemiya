<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

/**
 * Wrapper for BCMath to provide arbitrary-precision arithmetic for Metrology.
 * Standard scale is 10 decimal places.
 */
class PreciseMath
{
    private const SCALE = 10;

    public static function add(string|float|int $a, string|float|int $b): string
    {
        return bcadd((string) $a, (string) $b, self::SCALE);
    }

    public static function sub(string|float|int $a, string|float|int $b): string
    {
        return bcsub((string) $a, (string) $b, self::SCALE);
    }

    public static function mul(string|float|int $a, string|float|int $b): string
    {
        return bcmul((string) $a, (string) $b, self::SCALE);
    }

    public static function div(string|float|int $a, string|float|int $b): string
    {
        if (bccomp((string) $b, '0', self::SCALE) === 0) {
            return '0';
        }

        return bcdiv((string) $a, (string) $b, self::SCALE);
    }

    public static function pow(string|float|int $a, string|float|int $b): string
    {
        return bcpow((string) $a, (string) $b, self::SCALE);
    }

    public static function sqrt(string|float|int $a): string
    {
        return bcsqrt((string) $a, self::SCALE);
    }

    public static function comp(string|float|int $a, string|float|int $b): int
    {
        return bccomp((string) $a, (string) $b, self::SCALE);
    }

    /**
     * Calcula o quadrado de um número (x^2)
     */
    public static function square(string|float|int $a): string
    {
        return bcmul((string) $a, (string) $a, self::SCALE);
    }
}
