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
