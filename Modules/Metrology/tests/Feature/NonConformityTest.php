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

it('can create a non-conformity report', function () {
    $instrument = \Modules\Metrology\Models\Instrument::factory()->create();

    $payload = [
        'item_type' => \Modules\Metrology\Models\Instrument::class,
        'item_id' => $instrument->id,
        'title' => 'Desvio metrológico detectado no micrômetro',
        'description' => 'Erro sistemático excedeu o limite do critério de aceitação de 0.005mm.',
        'priority' => 'high',
        'immediate_action' => 'Instrumento interditado e identificado com etiqueta vermelha.',
    ];

    $response = $this->postJson('/api/v1/metrology/non-conformities', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'Desvio metrológico detectado no micrômetro')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('non_conformities', [
        'item_id' => $instrument->id,
        'title' => 'Desvio metrológico detectado no micrômetro',
        'status' => 'open',
    ]);
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
    $nc = NonConformity::factory()->create([
        'status' => 'resolved',
        'root_cause_analysis' => 'Root cause identified',
        'corrective_action' => 'Corrective action implemented',
    ]);

    $response = $this->postJson("/api/v1/metrology/non-conformities/{$nc->id}/close", [
        'resolution' => 'Everything fixed',
    ]);

    $response->assertStatus(200);
    expect($nc->refresh()->status)->toBe('closed')
        ->and($nc->closed_at)->not->toBeNull();
});
