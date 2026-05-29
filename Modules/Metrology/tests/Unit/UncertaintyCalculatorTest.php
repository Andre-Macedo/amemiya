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
