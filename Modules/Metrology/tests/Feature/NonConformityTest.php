<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\NonConformity;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list non-conformities', function () {
    NonConformity::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/metrology/non-conformities');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can update a non-conformity investigation', function () {
    $nc = NonConformity::factory()->create(['status' => 'open']);

    $data = [
        'root_cause_analysis' => 'Human error',
        'corrective_action' => 'Retraining',
        'status' => 'investigating',
    ];

    $response = $this->putJson("/api/v1/metrology/non-conformities/{$nc->id}", $data);

    $response->assertStatus(200);
    $this->assertDatabaseHas('non_conformities', [
        'id' => $nc->id,
        'root_cause_analysis' => 'Human error',
        'status' => 'investigating',
    ]);
});

it('can close a non-conformity', function () {
    $nc = NonConformity::factory()->create(['status' => 'resolved']);

    $response = $this->postJson("/api/v1/metrology/non-conformities/{$nc->id}/close", [
        'resolution' => 'Everything fixed',
    ]);

    $response->assertStatus(200);
    expect($nc->refresh()->status)->toBe('closed')
        ->and($nc->closed_at)->not->toBeNull();
});
