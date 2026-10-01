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
