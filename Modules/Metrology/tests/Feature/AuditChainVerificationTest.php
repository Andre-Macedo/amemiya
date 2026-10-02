<?php

declare(strict_types=1);

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Instrument;
use Modules\System\Services\AuditChainService;
use Tests\Concerns\HasSuperAdmin;

uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function (): void {
    Artisan::call('module:migrate', ['module' => 'Metrology']);
    $user = $this->createSuperAdmin();
    Sanctum::actingAs($user);
});

test('it chains audit logs sequentially with cryptographic hashes', function (): void {
    $service = app(AuditChainService::class);

    // Bloco 1 (Gênesis)
    $log1 = Activity::create([
        'log_name' => 'metrology',
        'description' => 'Instrument created',
        'subject_type' => Instrument::class,
        'subject_id' => '01JABCDEF12345678901234567',
    ]);

    expect($log1->sequence_number)->toBe(1)
        ->and($log1->previous_hash)->toBe(AuditChainService::GENESIS_HASH)
        ->and($log1->record_hash)->toBeString()
        ->and(strlen($log1->record_hash))->toBe(64);

    // Bloco 2
    $log2 = Activity::create([
        'log_name' => 'metrology',
        'description' => 'Instrument calibrated',
        'subject_type' => Instrument::class,
        'subject_id' => '01JABCDEF12345678901234567',
    ]);

    expect($log2->sequence_number)->toBe(2)
        ->and($log2->previous_hash)->toBe($log1->record_hash)
        ->and($log2->record_hash)->toBeString()
        ->and(strlen($log2->record_hash))->toBe(64);

    // Bloco 3
    $log3 = Activity::create([
        'log_name' => 'metrology',
        'description' => 'Calibration approved with guard band',
        'subject_type' => Instrument::class,
        'subject_id' => '01JABCDEF12345678901234567',
    ]);

    expect($log3->sequence_number)->toBe(3)
        ->and($log3->previous_hash)->toBe($log2->record_hash);

    // Verificação de cadeia íntegra
    $report = $service->verifyChain();
    expect($report['is_valid'])->toBeTrue()
        ->and($report['total_records'])->toBe(3)
        ->and($report['chain_head_hash'])->toBe($log3->record_hash)
        ->and($report['errors'])->toBeEmpty();
});

test('it detects manual database data tampering in audit log chain', function (): void {
    $service = app(AuditChainService::class);

    $log1 = Activity::create([
        'log_name' => 'metrology',
        'description' => 'Original Log Entry 1',
    ]);

    $log2 = Activity::create([
        'log_name' => 'metrology',
        'description' => 'Original Log Entry 2',
    ]);

    // Simula adulteração criminosa direta no banco de dados (bypass de aplicação)
    DB::table('activity_log')
        ->where('id', $log1->id)
        ->update(['description' => 'Adulteração Maliciosa de Dados']);

    $report = $service->verifyChain();

    expect($report['is_valid'])->toBeFalse()
        ->and($report['status'])->toBe('COMPROMISED')
        ->and(count($report['errors']))->toBeGreaterThan(0)
        ->and($report['errors'][0]['type'])->toBe('DATA_TAMPERING_DETECTED');
});

test('it detects deletion of records causing a sequence gap in the chain', function (): void {
    $service = app(AuditChainService::class);

    Activity::create(['description' => 'Log 1']);
    $log2 = Activity::create(['description' => 'Log 2']);
    Activity::create(['description' => 'Log 3']);

    // Deleta o registro intermediário #2 para simular ocultação de provas
    DB::table('activity_log')->where('id', $log2->id)->delete();

    $report = $service->verifyChain();

    expect($report['is_valid'])->toBeFalse()
        ->and(count($report['errors']))->toBeGreaterThan(0);

    $gapError = collect($report['errors'])->firstWhere('type', 'SEQUENCE_GAP');
    expect($gapError)->not->toBeNull();
});

test('it verifies chain via api endpoint', function (): void {
    Activity::create(['description' => 'Test log']);

    $response = $this->getJson('/api/v1/audit-logs/verify-chain');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'is_valid',
            'total_records',
            'status',
            'genesis_hash',
            'chain_head_hash',
            'latest_sequence',
            'verified_at',
            'errors',
        ]);
});

test('it verifies audit chain via artisan command', function (): void {
    Activity::create(['description' => 'Artisan test log']);

    $this->artisan('metrology:verify-audit-chain')
        ->expectsOutputToContain('VERIFICAÇÃO FORENSE DE INTEGRIDADE DA CADEIA DE AUDITORIA')
        ->expectsOutputToContain('CADEIA 100% ÍNTEGRA')
        ->assertSuccessful();
});
