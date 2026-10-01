<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\ReferenceStandardType;
use Tests\Concerns\HasSuperAdmin;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function () {
    Artisan::call('module:migrate', ['module' => 'Metrology']);
    $user = $this->createSuperAdmin();
    Sanctum::actingAs($user);
});

it('can create a reference standard', function () {
    $this->createSuperAdmin();
    $type = ReferenceStandardType::factory()->create(['name' => 'Gauge Block']);

    $standard = ReferenceStandard::factory()->create([
        'reference_standard_type_id' => $type->id,
        'name' => 'Master Gauge Block Set',
    ]);

    expect($standard)
        ->name->toBe('Master Gauge Block Set')
        ->reference_standard_type_id->toBe($type->id);

    assertDatabaseHas('reference_standards', [
        'name' => 'Master Gauge Block Set',
        'reference_standard_type_id' => $type->id,
    ]);
});

it('calculates next calibration due date based on type frequency', function () {
    // 1. Cria Tipo com frequência de 24 meses
    $type = ReferenceStandardType::factory()->create(['calibration_frequency_months' => 24]);
    $standard = ReferenceStandard::factory()->create(['reference_standard_type_id' => $type->id]);

    // 2. Cria uma calibração
    $calibrationDate = now()->subMonths(10);
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => ReferenceStandard::class,
        'calibrated_item_id' => $standard->id,
        'calibration_date' => $calibrationDate,
        'result' => 'approved',
    ]);

    // 3. Valida lógica de getNextCalibrationDueAttribute
    // Lógica: última calibração + frequência do tipo
    $expectedDate = $calibrationDate->copy()->addMonths(24)->startOfDay();

    expect($standard->next_calibration_due?->startOfDay()->equalTo($expectedDate))->toBeTrue();
});

it('resolves effective serial number from parent for kits', function () {
    $parent = ReferenceStandard::factory()->create(['serial_number' => 'KIT-123']);
    $child = ReferenceStandard::factory()->create([
        'parent_id' => $parent->id,
        'serial_number' => null, // Child has no serial, should inherit
    ]);

    expect($child->effective_serial_number)->toBe('KIT-123 (Kit)');
});

it('persists and retrieves rbc traceability chain information', function () {
    $type = ReferenceStandardType::factory()->create(['name' => 'Anel Padrao']);

    $standard = ReferenceStandard::factory()->create([
        'reference_standard_type_id' => $type->id,
        'name' => 'Anel Padrao 25mm',
        'certificate_number' => 'CAL-0891/2026',
        'accredited_lab' => 'Mitutoyo Sul Americana - RBC CAL 0031',
        'traceability_chain' => 'Padrao Primario LNM/Inmetro rastreado ao BIPM',
    ]);

    expect($standard)
        ->certificate_number->toBe('CAL-0891/2026')
        ->accredited_lab->toBe('Mitutoyo Sul Americana - RBC CAL 0031')
        ->traceability_chain->toBe('Padrao Primario LNM/Inmetro rastreado ao BIPM');

    assertDatabaseHas('reference_standards', [
        'id' => $standard->id,
        'certificate_number' => 'CAL-0891/2026',
        'accredited_lab' => 'Mitutoyo Sul Americana - RBC CAL 0031',
        'traceability_chain' => 'Padrao Primario LNM/Inmetro rastreado ao BIPM',
    ]);
});

it('serializes rbc traceability fields via api resource', function () {
    $type = ReferenceStandardType::factory()->create();
    $standard = ReferenceStandard::factory()->create([
        'reference_standard_type_id' => $type->id,
        'certificate_number' => 'CERT-2026-999',
        'accredited_lab' => 'Laboratório Metrológico RBC 0123',
        'traceability_chain' => 'Rastreado à Rede Brasileira de Calibração (RBC/Inmetro)',
    ]);

    $response = $this->getJson("/api/v1/standards/{$standard->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.certificate_number', 'CERT-2026-999');
    $response->assertJsonPath('data.accredited_lab', 'Laboratório Metrológico RBC 0123');
    $response->assertJsonPath('data.traceability_chain', 'Rastreado à Rede Brasileira de Calibração (RBC/Inmetro)');
});

it('can store reference standard with rbc traceability fields via api', function () {
    $type = ReferenceStandardType::factory()->create();

    $payload = [
        'name' => 'Bloco Padrão Cerâmico 100mm',
        'serial_number' => 'BP-100-99',
        'reference_standard_type_id' => $type->id,
        'status' => 'active',
        'certificate_number' => 'RBC-CAL-5544',
        'accredited_lab' => 'Certi / Fundação CERTI RBC 0015',
        'traceability_chain' => 'Inmetro -> BIPM (Bureau International des Poids et Mesures)',
    ];

    $response = $this->postJson('/api/v1/standards', $payload);

    $response->assertStatus(201);
    $response->assertJsonPath('data.certificate_number', 'RBC-CAL-5544');
    $response->assertJsonPath('data.accredited_lab', 'Certi / Fundação CERTI RBC 0015');
    $response->assertJsonPath('data.traceability_chain', 'Inmetro -> BIPM (Bureau International des Poids et Mesures)');
});
