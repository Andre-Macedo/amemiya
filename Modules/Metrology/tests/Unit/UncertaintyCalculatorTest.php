<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\DTOs\MeasurementCalculationData;
use Modules\Metrology\DTOs\UncertaintyResult;
use Modules\Metrology\Services\UncertaintyCalculator;

test('it calculates uncertainty correctly', function () {
    $calculator = new UncertaintyCalculator;

    // Data from GUM example
    $readings = [10.01, 10.02, 10.01, 10.03, 10.02];
    $resolution = 0.01;
    $standardResult = 10.00;
    $standardUncertainty = 0.005;
    $standardK = 2.0;

    $data = new MeasurementCalculationData(
        readings: $readings,
        resolution: $resolution,
        standardActualValue: $standardResult,
        standardUncertainty: $standardUncertainty,
        standardK: $standardK
    );

    $result = $calculator->calculate($data);

    expect($result)
        ->toBeInstanceOf(UncertaintyResult::class);

    // Bias: Mean 10.018 - Standard 10.00 = 0.018
    expect($result->bias)->toEqualWithDelta(0.018, 0.0001);

    expect($result->expandedUncertainty)->toBeGreaterThan(0);
});

test('it returns zeros for empty readings', function () {
    $calculator = new UncertaintyCalculator;
    $data = new MeasurementCalculationData(
        readings: [],
        resolution: 0.01,
        standardActualValue: 10.00
    );
    $result = $calculator->calculate($data);

    expect($result->bias)->toBe(0.0)
        ->and($result->expandedUncertainty)->toBe(0.0);
});

test('it calculates dynamic k factor via welch satterthwaite for small n', function () {
    $calculator = new UncertaintyCalculator;

    // n=3 com dispersão: uA domina, veff ≈ 2, k deve ser próximo de 4.30
    $data = new MeasurementCalculationData(
        readings: [10.00, 10.05, 10.10],
        resolution: 0.0001,
        standardActualValue: 10.00,
        standardUncertainty: 0.0001,
        standardK: 2.0,
    );

    $result = $calculator->calculate($data);

    expect($result->kFactor)->toBeGreaterThan(3.5)
        ->and($result->kFactor)->toBeLessThan(5.0)
        ->and($result->effectiveDegreesOfFreedom)->toBeLessThan(5.0);
});

test('it approaches k 2.00 for large n or dominant type b', function () {
    $calculator = new UncertaintyCalculator;

    // n=30 com uA quase nulo
    $data = new MeasurementCalculationData(
        readings: array_fill(0, 30, 10.01),
        resolution: 0.01,
        standardActualValue: 10.00,
        standardUncertainty: 0.005,
        standardK: 2.0,
    );

    $result = $calculator->calculate($data);

    expect($result->kFactor)->toBeLessThanOrEqual(2.10);
});

test('it applies conservative floor truncation to veff per GUM G.4.2 and EA-4/02', function () {
    // veff = 4.8 deve truncar para 4 (k = 2.776) em vez de arredondar para 5 (k = 2.571)
    $k = \Modules\Metrology\Services\MetrologyMath::getKFromVeff(4.8);
    expect($k)->toBe(2.776);

    // veff = 2.9 deve truncar para 2 (k = 4.303)
    $k2 = \Modules\Metrology\Services\MetrologyMath::getKFromVeff(2.9);
    expect($k2)->toBe(4.303);

    // veff = 22.0 deve interpolar entre 20 (2.086) e 25 (2.060): 2.086 - 0.0104 = 2.076
    $k3 = \Modules\Metrology\Services\MetrologyMath::getKFromVeff(22.0);
    expect($k3)->toBe(2.076);
});
