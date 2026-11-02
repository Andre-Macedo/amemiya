<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Exceptions\MpeNotResolvableException;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Services\MpeCalculator;

test('absolute mpe returns fixed value regardless of nominal', function () {
    $instrument = new Instrument([
        'mpe_type' => 'absolute',
        'mpe_value' => 0.05,
    ]);

    expect(MpeCalculator::resolve($instrument, 100.0))->toBe(0.05)
        ->and(MpeCalculator::resolve($instrument, 50.0))->toBe(0.05)
        ->and(MpeCalculator::resolve($instrument, null))->toBe(0.05);
});

test('percentage mpe scales with nominal value', function () {
    $instrument = new Instrument([
        'mpe_type' => 'percentage',
        'mpe_value' => 0.5,
    ]);

    expect(MpeCalculator::resolve($instrument, 100.0))->toEqualWithDelta(0.50, 0.0001)
        ->and(MpeCalculator::resolve($instrument, 50.0))->toEqualWithDelta(0.25, 0.0001)
        ->and(MpeCalculator::resolve($instrument, 220.0))->toEqualWithDelta(1.10, 0.0001);
});

test('percentage mpe without nominal throws MpeNotResolvableException', function () {
    $instrument = new Instrument([
        'mpe_type' => 'percentage',
        'mpe_value' => 0.5,
    ]);

    expect(fn () => MpeCalculator::resolve($instrument, null))
        ->toThrow(MpeNotResolvableException::class);
});

test('backward compatibility parses percentage from string mpe', function () {
    $instrument = new Instrument([
        'mpe' => '0.2%',
    ]);

    expect(MpeCalculator::resolve($instrument, 100.0))->toEqualWithDelta(0.20, 0.0001);
});

test('null or zero mpe returns zero', function () {
    $instrument = new Instrument([
        'mpe_value' => 0.0,
    ]);

    expect(MpeCalculator::resolve($instrument, 100.0))->toBe(0.0);
});

test('compound mpe avoids zero tolerance when nominal value is zero', function () {
    $instrument = new Instrument([
        'mpe' => '0.05% + 0.01',
    ]);

    // No ponto zero, a tolerância não pode ser zero (evita rejeição espúria por micro-ruído)
    expect(MpeCalculator::resolve($instrument, 0.0))->toBe(0.01)
        ->and(MpeCalculator::resolve($instrument, 100.0))->toEqualWithDelta(0.06, 0.000001)
        ->and(MpeCalculator::resolve($instrument, 50.0))->toEqualWithDelta(0.035, 0.000001);
});

test('compound mpe supports fluke benchmark specification with digits and resolution', function () {
    $instrument = new Instrument([
        'mpe' => '±(0.09% rdg + 2d)',
        'resolution' => '0.01',
    ]);

    // Nominal 0.0: 0% + 2 * 0.01 = 0.02
    expect(MpeCalculator::resolve($instrument, 0.0))->toBe(0.02)
        // Nominal 100.0: 0.09 + 0.02 = 0.11
        ->and(MpeCalculator::resolve($instrument, 100.0))->toEqualWithDelta(0.11, 0.000001);
});

test('compound mpe supports beamex benchmark specification with full scale and span', function () {
    $instrument = new Instrument([
        'mpe' => '0.025% rdg + 0.01% FS',
        'measuring_range' => '0-100 bar',
    ]);

    // Span é 100.0. No nominal 0.0: 0 + 0.01% * 100 = 0.01 bar
    expect(MpeCalculator::resolve($instrument, 0.0))->toBe(0.01)
        // No nominal 50.0: (0.025% * 50) + 0.01 = 0.0125 + 0.01 = 0.0225 bar
        ->and(MpeCalculator::resolve($instrument, 50.0))->toEqualWithDelta(0.0225, 0.000001);
});

test('compound mpe supports three-term formula reading plus fs plus digits', function () {
    $instrument = new Instrument([
        'mpe' => '0.05% rdg + 0.02% FS + 1 digit',
        'measuring_range' => '0-150mm',
        'resolution' => '0.01mm',
    ]);

    // Span = 150.0, Res = 0.01.
    // FS comp = 0.02% * 150 = 0.03
    // Digits comp = 1 * 0.01 = 0.01
    // Fixed base at zero = 0.04
    expect(MpeCalculator::resolve($instrument, 0.0))->toBe(0.04)
        // Nominal 100.0: 0.05 + 0.03 + 0.01 = 0.09
        ->and(MpeCalculator::resolve($instrument, 100.0))->toEqualWithDelta(0.09, 0.000001);
});

test('compound mpe throws exception when required span or resolution is missing', function () {
    $instWithoutSpan = new Instrument([
        'mpe' => '0.05% rdg + 0.02% FS',
    ]);

    expect(fn () => MpeCalculator::resolve($instWithoutSpan, 10.0))
        ->toThrow(MpeNotResolvableException::class);

    $instWithoutRes = new Instrument([
        'mpe' => '0.05% rdg + 2d',
    ]);

    expect(fn () => MpeCalculator::resolve($instWithoutRes, 10.0))
        ->toThrow(MpeNotResolvableException::class);
});

test('span and resolution extractors handle multiple industrial string formats', function () {
    expect(MpeCalculator::extractSpan('0-150mm'))->toBe(150.0)
        ->and(MpeCalculator::extractSpan('25-50mm'))->toBe(25.0)
        ->and(MpeCalculator::extractSpan('-20 to 100 °C'))->toBe(120.0)
        ->and(MpeCalculator::extractSpan('0..10 bar'))->toBe(10.0)
        ->and(MpeCalculator::extractSpan('100 V'))->toBe(100.0)
        ->and(MpeCalculator::extractSpan(null))->toBeNull();

    expect(MpeCalculator::extractResolution('0.01 mm'))->toBe(0.01)
        ->and(MpeCalculator::extractResolution('0,001'))->toBe(0.001)
        ->and(MpeCalculator::extractResolution(0.05))->toBe(0.05)
        ->and(MpeCalculator::extractResolution(null))->toBeNull();
});
