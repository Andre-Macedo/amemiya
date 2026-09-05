<?php

declare(strict_types=1);

use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\LabClient;

it('authenticates a lab client with valid CNPJ and token and returns a Sanctum token', function () {
    $client = LabClient::create([
        'name' => 'Empresa Teste Metrologia',
        'cnpj' => '12.345.678/0001-90',
        'email' => 'contato@empresateste.com.br',
        'access_token' => 'TESTE123TOKEN',
    ]);

    $response = $this->postJson('/api/v1/public/portal/login', [
        'cnpj' => '12.345.678/0001-90',
        'token' => 'TESTE123TOKEN',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'client' => ['id', 'name', 'cnpj', 'email'],
            'branding' => ['lab_name'],
            'auth_token',
        ]);

    expect($response->json('auth_token'))->toBeString()->not->toBeEmpty();
});

it('rejects authentication with invalid credentials', function () {
    $response = $this->postJson('/api/v1/public/portal/login', [
        'cnpj' => '00.000.000/0000-00',
        'token' => 'INVALID_TOKEN',
    ]);

    $response->assertStatus(401);
});

it('allows authenticated client to access me and list their certificates only', function () {
    $clientA = LabClient::create([
        'name' => 'Cliente A',
        'cnpj' => '11.111.111/0001-11',
        'access_token' => 'TOKENA111111',
    ]);

    $clientB = LabClient::create([
        'name' => 'Cliente B',
        'cnpj' => '22.222.222/0002-22',
        'access_token' => 'TOKENB222222',
    ]);

    $instrumentA = Instrument::factory()->create([
        'name' => 'Paquímetro Cliente A',
        'serial_number' => 'SN-A-01',
        'status' => ItemStatus::Active,
        'lab_client_id' => $clientA->id,
    ]);

    $instrumentB = Instrument::factory()->create([
        'name' => 'Micrômetro Cliente B',
        'serial_number' => 'SN-B-01',
        'status' => ItemStatus::Active,
        'lab_client_id' => $clientB->id,
    ]);

    $calA = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrumentA->id,
        'lab_client_id' => $clientA->id,
        'status' => 'published',
        'result' => CalibrationResult::Approved,
        'calibration_date' => now(),
    ]);

    $calB = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrumentB->id,
        'lab_client_id' => $clientB->id,
        'status' => 'published',
        'result' => CalibrationResult::Approved,
        'calibration_date' => now(),
    ]);

    $tokenA = $clientA->createToken('portal_access')->plainTextToken;

    // Acessar /me com token A
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/v1/public/portal/me');
    $meResponse->assertOk()
        ->assertJsonPath('client.name', 'Cliente A');

    // Listar certificados com token A
    $certsResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->getJson('/api/v1/public/portal/certificates');
    $certsResponse->assertOk();

    $ids = collect($certsResponse->json())->pluck('id');
    expect($ids)->toContain($calA->id)
        ->and($ids)->not->toContain($calB->id);
});

it('downloads multiple certificates in a zip package for the client', function () {
    $client = LabClient::create([
        'name' => 'Empresa Peças Industriais',
        'cnpj' => '33.333.333/0001-33',
        'access_token' => 'TOKENZIP1234',
    ]);

    $instrument = Instrument::factory()->create([
        'name' => 'Relógio Comparador',
        'serial_number' => 'RC-9988',
        'status' => ItemStatus::Active,
        'lab_client_id' => $client->id,
    ]);

    $cal = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'lab_client_id' => $client->id,
        'status' => 'published',
        'result' => CalibrationResult::Approved,
        'calibration_date' => now(),
    ]);

    $token = $client->createToken('portal_access')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/public/portal/certificates/download-zip', [
            'ids' => [$cal->id],
        ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/zip');
});

it('lists client instruments with calibration due status', function () {
    $client = LabClient::create([
        'name' => 'Cliente Parque',
        'cnpj' => '44.444.444/0001-44',
        'access_token' => 'TOKENPARQUE1',
    ]);

    $inst1 = Instrument::factory()->create([
        'name' => 'Termômetro Digital',
        'serial_number' => 'TERM-01',
        'calibration_due' => now()->addMonths(6),
        'status' => ItemStatus::Active,
        'lab_client_id' => $client->id,
    ]);

    $inst2 = Instrument::factory()->create([
        'name' => 'Manômetro Vencido',
        'serial_number' => 'MANO-02',
        'calibration_due' => now()->subDays(5),
        'status' => ItemStatus::Active,
        'lab_client_id' => $client->id,
    ]);

    $token = $client->createToken('portal_access')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/public/portal/instruments');

    $response->assertOk();
    $items = collect($response->json());

    expect($items)->toHaveCount(2);

    $term = $items->firstWhere('serial_number', 'TERM-01');
    $mano = $items->firstWhere('serial_number', 'MANO-02');

    expect($term['status'])->toBe('valid');
    expect($mano['status'])->toBe('expired');
});
