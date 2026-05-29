<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\WorkOrder;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list work orders', function () {
    WorkOrder::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/metrology/work-orders');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can create a work order for an instrument', function () {
    $instrument = Instrument::factory()->create();

    $data = [
        'item_id' => $instrument->id,
        'item_type' => Instrument::class,
        'visual_inspection_notes' => 'Minor scratches on screen',
        'customer_notes' => 'Please calibrate fast',
        'expected_return_date' => now()->addDays(5)->toDateString(),
        'status' => 'received',
    ];

    $response = $this->postJson('/api/v1/metrology/work-orders', $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.item_name', $instrument->name)
        ->assertJsonPath('data.status', 'received');

    $this->assertDatabaseHas('work_orders', [
        'item_id' => $instrument->id,
        'visual_inspection_notes' => 'Minor scratches on screen',
    ]);
});

it('can update a work order status', function () {
    $workOrder = WorkOrder::factory()->create(['status' => 'received']);

    $response = $this->putJson("/api/v1/metrology/work-orders/{$workOrder->id}", [
        'status' => 'in_queue',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'in_queue');

    expect($workOrder->refresh()->status)->toBe('in_queue');
});

it('can delete a work order', function () {
    $workOrder = WorkOrder::factory()->create();

    $response = $this->deleteJson("/api/v1/metrology/work-orders/{$workOrder->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted($workOrder);
});
