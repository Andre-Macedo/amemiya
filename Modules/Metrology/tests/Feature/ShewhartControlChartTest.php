<?php

declare(strict_types=1);

use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\IntermediateCheck;
use Modules\Metrology\Services\ShewhartControlChartService;
use Modules\System\Models\User;

test('intermediate check automatically calculates deviation on save', function () {
    $instrument = Instrument::factory()->create();
    $user = User::factory()->create();

    $check = IntermediateCheck::create([
        'instrument_id' => $instrument->id,
        'check_date' => now(),
        'result' => 'passed',
        'nominal_value' => 50.000,
        'measured_value' => 50.015,
        'performed_by' => $user->id,
    ]);

    expect((float) $check->deviation)->toBe(0.015);
});

test('shewhart service reports insufficient data when fewer than 2 checks', function () {
    $instrument = Instrument::factory()->create();
    $user = User::factory()->create();

    IntermediateCheck::create([
        'instrument_id' => $instrument->id,
        'check_date' => now(),
        'result' => 'passed',
        'deviation' => 0.005,
        'performed_by' => $user->id,
    ]);

    $service = app(ShewhartControlChartService::class);
    $analysis = $service->analyze($instrument);

    expect($analysis['has_sufficient_data'])->toBeFalse()
        ->and($analysis['statistics'])->toBeNull()
        ->and($analysis['total_checks'])->toBe(1);
});

test('shewhart service computes statistical 3-sigma control limits correctly', function () {
    $instrument = Instrument::factory()->create([
        'mpe_value' => 0.050,
    ]);
    $user = User::factory()->create();

    // 5 medições com média 0.010 e desvios conhecidos
    $deviations = [0.008, 0.010, 0.012, 0.009, 0.011];
    foreach ($deviations as $idx => $dev) {
        IntermediateCheck::create([
            'instrument_id' => $instrument->id,
            'check_date' => now()->subDays(5 - $idx),
            'result' => 'passed',
            'nominal_value' => 25.000,
            'measured_value' => 25.000 + $dev,
            'deviation' => $dev,
            'performed_by' => $user->id,
        ]);
    }

    $service = app(ShewhartControlChartService::class);
    $analysis = $service->analyze($instrument);

    expect($analysis['has_sufficient_data'])->toBeTrue()
        ->and($analysis['in_control'])->toBeTrue()
        ->and($analysis['statistics']['mean'])->toBe(0.01)
        ->and($analysis['statistics']['ucl'])->toBeGreaterThan(0.01)
        ->and($analysis['statistics']['lcl'])->toBeLessThan(0.01)
        ->and($analysis['statistics']['usl'])->toBe(0.05)
        ->and($analysis['statistics']['lsl'])->toBe(-0.05);
});

test('shewhart service detects out-of-control point violating 3-sigma limits', function () {
    $instrument = Instrument::factory()->create([
        'mpe_value' => 0.100,
    ]);
    $user = User::factory()->create();

    // 10 pontos estáveis e 1 ponto discrepante (outlier)
    $deviations = array_merge(array_fill(0, 10, 0.002), [0.050]);
    foreach ($deviations as $idx => $dev) {
        IntermediateCheck::create([
            'instrument_id' => $instrument->id,
            'check_date' => now()->subDays(count($deviations) - $idx),
            'result' => $dev > 0.04 ? 'failed' : 'passed',
            'deviation' => $dev,
            'performed_by' => $user->id,
        ]);
    }

    $service = app(ShewhartControlChartService::class);
    $analysis = $service->analyze($instrument);

    expect($analysis['in_control'])->toBeFalse()
        ->and(count($analysis['alerts']))->toBeGreaterThan(0)
        ->and($analysis['points'][10]['is_out_of_control'])->toBeTrue();
});

test('shewhart api endpoint returns complete control chart payload', function () {
    $instrument = Instrument::factory()->create([
        'mpe_value' => 0.030,
    ]);
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        IntermediateCheck::create([
            'instrument_id' => $instrument->id,
            'check_date' => now()->subDays(3 - $i),
            'result' => 'passed',
            'nominal_value' => 10.0,
            'measured_value' => 10.005,
            'deviation' => 0.005,
            'performed_by' => $user->id,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/instruments/{$instrument->id}/intermediate-checks/shewhart");

    $response->assertOk()
        ->assertJsonStructure([
            'instrument_id',
            'instrument_name',
            'mpe',
            'total_checks',
            'has_sufficient_data',
            'statistics' => [
                'mean',
                'std_dev',
                'ucl',
                'lcl',
                'uwl',
                'lwl',
                'usl',
                'lsl',
            ],
            'in_control',
            'alerts',
            'points' => [
                '*' => [
                    'id',
                    'check_date',
                    'formatted_date',
                    'nominal_value',
                    'measured_value',
                    'deviation',
                    'result',
                    'is_out_of_control',
                ],
            ],
        ]);
});
