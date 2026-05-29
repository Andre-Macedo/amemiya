<?php

use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\AuditLog;
use Modules\System\Models\User;

it('does not update instrument due date when calibration is draft', function () {
    // Arrange
    $user = User::factory()->create();
    actingAs($user);

    $instrument = Instrument::factory()->create([
        'calibration_due' => now()->subDays(10), // Vencido
        'status' => ItemStatus::Active,
    ]);

    $oldDueDate = $instrument->calibration_due;

    // Act
    Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'calibration_date' => now(),
        'result' => CalibrationResult::Approved,
        'status' => 'draft',
        'deviation' => 0.01,
        'uncertainty' => 0.01,
    ]);

    // Assert: Instrumento NÃO deve ter mudado
    $instrument->refresh();
    expect($instrument->calibration_due->toDateString())->toBe($oldDueDate->toDateString());
});

it('updates instrument and logs audit when calibration is published', function () {
    // Arrange
    $user = User::factory()->create();
    actingAs($user);

    $instrument = Instrument::factory()->create([
        'calibration_due' => now()->subDays(10),
        'status' => ItemStatus::Active,
    ]);

    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'calibration_date' => now(),
        'result' => CalibrationResult::Approved,
        'status' => 'draft',
        'deviation' => 0.01,
        'uncertainty' => 0.01,
        'performed_by_id' => $user->id,
    ]);

    // Act: Aprovar/Publicar
    $calibration->update([
        'status' => 'published',
        'approved_by_id' => $user->id,
        'approved_at' => now(),
    ]);

    // Assert 1: Data de Vencimento do Instrumento Atualizada
    $instrument->refresh();
    expect($instrument->calibration_due->gt(now()))->toBeTrue();

    // Assert 2: Log de Auditoria Criado
    $log = AuditLog::where('auditable_type', Instrument::class)
        ->where('auditable_id', $instrument->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values)->toHaveKey('calibration_due')
        ->and($log->user_id)->toBe($user->id);
});
