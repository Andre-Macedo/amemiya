<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Services\UncertaintyCalculator;

test('it calculates uncertainty correctly', function () {
    $calculator = new UncertaintyCalculator();

    // Data from GUM example
    $readings = [10.01, 10.02, 10.01, 10.03, 10.02];
    $resolution = 0.01;
    $standardResult = 10.00;
    $standardUncertainty = 0.005;
    $standardK = 2.0;

    $result = $calculator->calculate(
        $readings,
        $resolution,
        $standardResult,
        $standardUncertainty,
        $standardK
    );

    expect($result)
        ->toHaveKey('bias')
        ->toHaveKey('expanded_uncertainty')
        ->toHaveKey('budget');

    // Bias: Mean 10.018 - Standard 10.00 = 0.018
    expect($result['bias'])->toEqualWithDelta(0.018, 0.0001);

    expect($result['expanded_uncertainty'])->toBeGreaterThan(0);
});

test('it returns zeros for empty readings', function () {
    $calculator = new UncertaintyCalculator();
    $result = $calculator->calculate([], 0.01, 10.00);

    expect($result['bias'])->toBe(0.0)
        ->and($result['expanded_uncertainty'])->toBe(0.0);
});
