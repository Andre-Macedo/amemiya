<?php

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;

it('can create instrument with factory', function () {
    $instrument = Instrument::factory()->make();

    expect($instrument)->toBeInstanceOf(Instrument::class)
        ->and($instrument->status)->toBeInstanceOf(ItemStatus::class);
});
