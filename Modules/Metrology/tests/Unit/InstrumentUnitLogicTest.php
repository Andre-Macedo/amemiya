<?php

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Models\Instrument;
use Tests\TestCase;

uses(TestCase::class);

test('it parses mpe strings correctly', function () {
    $instrument = new Instrument();
    
    $instrument->mpe = '0.05';
    expect($instrument->getMpeValue())->toBe(0.05);

    $instrument->mpe = '0,05'; // Comma
    expect($instrument->getMpeValue())->toBe(0.05);

    $instrument->mpe = '0.05 mm'; // Unit
    expect($instrument->getMpeValue())->toBe(0.05);

    $instrument->mpe = '0,05mm'; // Comma + Unit
    expect($instrument->getMpeValue())->toBe(0.05);

    $instrument->mpe = null;
    expect($instrument->getMpeValue())->toBe(0.0);
});
