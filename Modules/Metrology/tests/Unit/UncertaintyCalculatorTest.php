<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\DTOs\MeasurementCalculationData;
use Modules\Metrology\DTOs\UncertaintyResult;
use Modules\Metrology\Services\MetrologyMath;
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
    $k = MetrologyMath::getKFromVeff(4.8);
    expect($k)->toBe(2.776);

    // veff = 2.9 deve truncar para 2 (k = 4.303)
    $k2 = MetrologyMath::getKFromVeff(2.9);
    expect($k2)->toBe(4.303);

    // veff = 22.0 deve interpolar entre 20 (2.086) e 25 (2.060): 2.086 - 0.0104 = 2.076
    $k3 = MetrologyMath::getKFromVeff(22.0);
    expect($k3)->toBe(2.076);
});

test('it combines multiple reference standards in quadrature per GUM JCGM 100:2008', function () {
    $calculator = new UncertaintyCalculator;

    // Dois blocos padrão combinados: 10 mm (U = 0.002, k = 2) e 25 mm (U = 0.003, k = 2)
    // u(S1) = 0.001 mm, u(S2) = 0.0015 mm
    // u_std_total = sqrt(0.001^2 + 0.0015^2) ≈ 0.0018027 mm
    $data = new MeasurementCalculationData(
        readings: [35.001, 35.002, 35.001, 35.001],
        resolution: 0.001,
        standards: [
            [
                'name' => 'Bloco Padrão 10mm (BP-10)',
                'actual_value' => 10.0002,
                'uncertainty' => 0.002,
                'k' => 2.0,
            ],
            [
                'name' => 'Bloco Padrão 25mm (BP-25)',
                'actual_value' => 24.9997,
                'uncertainty' => 0.003,
                'k' => 2.0,
            ],
        ]
    );

    $result = $calculator->calculate($data);

    // VME combinado: 10.0002 + 24.9997 = 34.9999
    // Média: 35.00125 -> Tendência = 35.00125 - 34.9999 = 0.00135
    expect($result->bias)->toEqualWithDelta(0.00135, 0.00005)
        ->and($result->budget)->toHaveCount(4); // Repeatability, Resolution, BP-10, BP-25

    $budgetSources = array_column($result->budget, 'source');
    expect($budgetSources)->toContain('Bloco Padrão 10mm (BP-10)')
        ->and($budgetSources)->toContain('Bloco Padrão 25mm (BP-25)');
});
