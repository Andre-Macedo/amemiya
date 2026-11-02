<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Actions\GenerateCertificatePdfAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\LabClient;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\System\Models\User;

uses(RefreshDatabase::class);

test('it generates valid pdf certificate content with iso17025 data', function () {
    $tenant = Tenant::create([
        'name' => 'Acme Labs',
        'slug' => 'acme-labs',
    ]);
    tenancy()->initialize($tenant);

    $user = User::factory()->create();
    $instrument = Instrument::factory()->create([
        'name' => 'Micrômetro Externo 0-25mm',
        'serial_number' => 'MIC-998877',
    ]);
    $standard = ReferenceStandard::factory()->create([
        'name' => 'Jogo de Blocos Padrão Grau 0',
        'serial_number' => 'BLK-001',
    ]);

    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'performed_by_id' => $user->id,
        'result' => CalibrationResult::Approved,
        'deviation' => 0.0012,
        'uncertainty' => 0.0035,
        'conformity_statement' => 'O item atende aos requisitos de erro máximo permissível (MPE) estabelecidos com regra de decisão Guard Band.',
    ]);

    $checklist = Checklist::factory()->create(['calibration_id' => $calibration->id]);

    ChecklistItem::factory()->create([
        'checklist_id' => $checklist->id,
        'step' => '5.00 mm',
        'nominal_value' => 5.00,
        'reference_standard_id' => $standard->id,
        'question_type' => 'numeric',
        'readings' => [['value' => 5.001], ['value' => 5.002]],
        'uncertainty' => 0.0032,
        'result' => 'approved',
    ]);

    $action = app(GenerateCertificatePdfAction::class);
    $pdf = $action->execute($calibration);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();
});

test('it renders iso17025 received condition and respects calibration due toggle', function () {
    $tenant = Tenant::create([
        'name' => 'Acme Labs 2',
        'slug' => 'acme-labs-2',
    ]);
    tenancy()->initialize($tenant);

    $user = User::factory()->create();
    $instrument = Instrument::factory()->create();

    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'performed_by_id' => $user->id,
        'as_received_condition' => 'Íntegro e limpo, sem sinais de oxidação',
        'received_date' => now()->subDays(2),
        'show_calibration_due' => false,
        'result' => CalibrationResult::Approved,
        'deviation' => 0.0010,
        'uncertainty' => 0.0020,
    ]);

    $action = app(GenerateCertificatePdfAction::class);
    $pdf = $action->execute($calibration);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();
});

test('it renders formal client details in certificate when associated with lab_client', function () {
    $tenant = Tenant::create([
        'name' => 'Acme Labs 3',
        'slug' => 'acme-labs-3',
    ]);
    tenancy()->initialize($tenant);

    $user = User::factory()->create();
    $client = LabClient::create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalúrgica Precision Ltda',
        'cnpj' => '12.345.678/0001-90',
        'email' => 'qualidade@precision.com.br',
        'metadata' => [
            'address' => 'Av. das Indústrias, 1500',
            'city' => 'Joinville',
            'state' => 'SC',
            'phone' => '(47) 3456-7890',
        ],
    ]);

    $instrument = Instrument::factory()->create([
        'lab_client_id' => $client->id,
    ]);

    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'performed_by_id' => $user->id,
        'lab_client_id' => $client->id,
        'result' => CalibrationResult::Approved,
        'deviation' => 0.0010,
        'uncertainty' => 0.0020,
    ]);

    $action = app(GenerateCertificatePdfAction::class);
    $pdf = $action->execute($calibration);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();

    // Verifica que os dados do cliente foram passados e renderizados na view do laudo
    $viewHtml = view('metrology::pdf.certificate', [
        'record' => $calibration,
        'instrument' => $instrument,
        'standards' => [],
        'results' => [],
        'identity' => [
            'lab_name' => 'Acme Labs 3',
            'lab_address' => '',
            'lab_contact' => '',
            'lab_logo_path' => null,
            'certificate_footer' => '',
            'accent_color' => '#3b82f6',
        ],
        'client' => [
            'is_internal' => false,
            'name' => 'Metalúrgica Precision Ltda',
            'document' => '12.345.678/0001-90',
            'address' => 'Av. das Indústrias, 1500 - Joinville - SC',
            'contact' => 'qualidade@precision.com.br',
            'phone' => '(47) 3456-7890',
            'email' => 'qualidade@precision.com.br',
        ],
    ])->render();

    expect($viewHtml)->toContain('1. Dados do Solicitante / Cliente (ISO/IEC 17025 §7.8.2.1e)')
        ->and($viewHtml)->toContain('Metalúrgica Precision Ltda')
        ->and($viewHtml)->toContain('12.345.678/0001-90')
        ->and($viewHtml)->toContain('Av. das Indústrias, 1500 - Joinville - SC')
        ->and($viewHtml)->toContain('qualidade@precision.com.br');
});

test('it renders internal calibration fallback when no lab_client is attached', function () {
    $tenant = Tenant::create([
        'name' => 'Acme Labs 4',
        'slug' => 'acme-labs-4',
    ]);
    tenancy()->initialize($tenant);

    $user = User::factory()->create();
    $instrument = Instrument::factory()->create([
        'lab_client_id' => null,
    ]);

    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'performed_by_id' => $user->id,
        'lab_client_id' => null,
        'result' => CalibrationResult::Approved,
        'deviation' => 0.0005,
        'uncertainty' => 0.0015,
    ]);

    $action = app(GenerateCertificatePdfAction::class);
    $pdf = $action->execute($calibration);

    expect($pdf)->toBeString()
        ->and(str_starts_with($pdf, '%PDF'))->toBeTrue();

    $viewHtml = view('metrology::pdf.certificate', [
        'record' => $calibration,
        'instrument' => $instrument,
        'standards' => [],
        'results' => [],
        'identity' => [
            'lab_name' => 'Acme Labs Metrologia',
            'lab_address' => 'Planta Industrial Bloco B',
            'lab_contact' => '',
            'lab_logo_path' => null,
            'certificate_footer' => '',
            'accent_color' => '#3b82f6',
        ],
        'client' => [
            'is_internal' => true,
            'name' => 'Acme Labs Metrologia',
            'document' => 'Uso Próprio / Calibração Interna',
            'address' => 'Planta Industrial Bloco B',
            'contact' => 'Setor de Gestão Metrológica',
            'phone' => null,
            'email' => null,
        ],
    ])->render();

    expect($viewHtml)->toContain('1. Dados do Solicitante / Cliente (ISO/IEC 17025 §7.8.2.1e)')
        ->and($viewHtml)->toContain('Calibração Interna / Uso Próprio')
        ->and($viewHtml)->toContain('Controle Metrológico Interno de Processo e Qualidade Industrial');
});
