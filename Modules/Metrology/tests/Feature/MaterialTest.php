<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Material;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list materials', function () {
    Material::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/metrology/materials');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('can create a material', function () {
    $data = [
        'name' => 'Titanium',
        'cte' => 8.6,
        'category' => 'Metal',
    ];

    $response = $this->postJson('/api/v1/metrology/materials', $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Titanium');

    $this->assertDatabaseHas('materials', ['name' => 'Titanium']);
});

it('can update a material', function () {
    $material = Material::factory()->create(['name' => 'Old Material']);

    $response = $this->putJson("/api/v1/metrology/materials/{$material->id}", [
        'name' => 'New Material',
        'cte' => 12.5,
    ]);

    $response->assertStatus(200);
    expect($material->refresh()->name)->toBe('New Material');
});

it('can delete a material', function () {
    $material = Material::factory()->create();

    $response = $this->deleteJson("/api/v1/metrology/materials/{$material->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('materials', ['id' => $material->id]);
});
