<?php

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Services\CalibrationValidator;

it('allows active items to be calibrated', function () {
    $instrument = new Instrument([
        'status' => ItemStatus::Active,
    ]);

    $validator = new CalibrationValidator;
    expect($validator->canBeCalibrated($instrument))->toBeTrue();
});

it('prevents rejected items from being calibrated', function () {
    $instrument = new Instrument([
        'status' => ItemStatus::Rejected,
    ]);

    $validator = new CalibrationValidator;

    expect(fn () => $validator->canBeCalibrated($instrument))
        ->toThrow(MetrologyException::class);
});

it('prevents lost items from being calibrated', function () {
    $instrument = new Instrument([
        'status' => ItemStatus::Lost,
    ]);

    $validator = new CalibrationValidator;

    expect(fn () => $validator->canBeCalibrated($instrument))
        ->toThrow(MetrologyException::class);
});

it('prevents scrapped items from being calibrated', function () {
    $instrument = new Instrument([
        'status' => ItemStatus::Scrapped,
    ]);

    $validator = new CalibrationValidator;

    expect(fn () => $validator->canBeCalibrated($instrument))
        ->toThrow(MetrologyException::class);
});
