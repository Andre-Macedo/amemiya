<?php

use Modules\Metrology\Models\Instrument;

test('it parses mpe strings correctly', function () {
    $instrument = new Instrument;

    // Use property directly to test legacy parsing via getMaximumPermissibleError
    $instrument->mpe = '0.05';
    expect($instrument->getMaximumPermissibleError())->toBe(0.05);

    $instrument->mpe = '0,05'; // Comma
    expect($instrument->getMaximumPermissibleError())->toBe(0.05);

    $instrument->mpe = '0.05 mm'; // Unit
    expect($instrument->getMaximumPermissibleError())->toBe(0.05);

    $instrument->mpe = '0,05mm'; // Comma + Unit
    expect($instrument->getMaximumPermissibleError())->toBe(0.05);

    $instrument->mpe = null;
    expect($instrument->getMaximumPermissibleError())->toBe(0.0);
});

test('it prioritizes mpe_value over legacy string', function () {
    $instrument = new Instrument([
        'mpe' => '0.05',
        'mpe_value' => 0.02,
    ]);

    expect($instrument->getMaximumPermissibleError())->toBe(0.02);
});
