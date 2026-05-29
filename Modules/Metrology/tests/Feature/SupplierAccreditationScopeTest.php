<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\Supplier;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can sync accreditation scope for a supplier', function () {
    $supplier = Supplier::factory()->create();
    $type1 = InstrumentType::factory()->create(['name' => 'Type 1']);
    $type2 = InstrumentType::factory()->create(['name' => 'Type 2']);

    $data = [
        'accreditations' => [
            [
                'instrument_type_id' => $type1->id,
                'range' => '0-150mm',
                'uncertainty' => '0.005mm',
            ],
            [
                'instrument_type_id' => $type2->id,
                'range' => '0-25kg',
                'uncertainty' => '0.01kg',
            ],
        ],
    ];

    $response = $this->postJson("/api/v1/metrology/suppliers/{$supplier->id}/accreditations", $data);

    $response->assertStatus(200);
    $this->assertDatabaseHas('supplier_accreditations', [
        'supplier_id' => $supplier->id,
        'instrument_type_id' => $type1->id,
        'range' => '0-150mm',
    ]);

    expect($supplier->accreditedInstrumentTypes)->toHaveCount(2);
});

it('can check if a supplier is accredited for a type', function () {
    $supplier = Supplier::factory()->create();
    $type = InstrumentType::factory()->create();

    $supplier->accreditedInstrumentTypes()->attach($type->id, ['range' => '0-10mm']);

    $response = $this->getJson("/api/v1/metrology/suppliers/{$supplier->id}/check-accreditation/{$type->id}");

    $response->assertStatus(200)
        ->assertJsonPath('is_accredited', true);
});
