<?php

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Services\CalibrationValidator;

it('allows active items to be calibrated', function () {
    $instrument = Instrument::factory()->create([
        'status' => ItemStatus::Active,
    ]);

    $validator = new CalibrationValidator;
    expect($validator->canBeCalibrated($instrument))->toBeTrue();
});

it('prevents rejected items from being calibrated', function () {
    $instrument = Instrument::factory()->create([
        'status' => ItemStatus::Rejected,
    ]);

    $validator = new CalibrationValidator;

    expect(fn () => $validator->canBeCalibrated($instrument))
        ->throws(MetrologyException::class, "Item status 'Reprovado' prevents calibration without maintenance.");
});

it('prevents lost items from being calibrated', function () {
    $instrument = Instrument::factory()->create([
        'status' => ItemStatus::Lost,
    ]);

    $validator = new CalibrationValidator;

    expect(fn () => $validator->canBeCalibrated($instrument))
        ->throws(MetrologyException::class);
});
