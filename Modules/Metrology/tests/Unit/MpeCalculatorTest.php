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
