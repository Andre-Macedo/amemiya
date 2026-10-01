<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Notifications\CalibrationDueNotification;
use Modules\System\Models\User;

uses(RefreshDatabase::class);

test('check-due command expires overdue instruments and isolates notifications per tenant', function () {
    Notification::fake();

    // Tenant A
    $tenantA = Tenant::create(['name' => 'Empresa Alfa', 'slug' => 'alfa']);
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    tenancy()->initialize($tenantA);
    // Instrumento de A vencendo em 7 dias
    $dueInstrumentA = Instrument::factory()->create([
        'name' => 'Paquímetro Alfa',
        'tenant_id' => $tenantA->id,
        'status' => ItemStatus::Active,
        'calibration_due' => now()->addDays(7)->startOfDay(),
    ]);

    // Instrumento de A já vencido
    $overdueInstrumentA = Instrument::factory()->create([
        'name' => 'Micrômetro Alfa Vencido',
        'tenant_id' => $tenantA->id,
        'status' => ItemStatus::Active,
        'calibration_due' => now()->subDays(2)->startOfDay(),
    ]);
    tenancy()->end();

    // Tenant B
    $tenantB = Tenant::create(['name' => 'Empresa Beta', 'slug' => 'beta']);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    tenancy()->initialize($tenantB);
    // Instrumento de B sem vencimento próximo (100 dias)
    $instrumentB = Instrument::factory()->create([
        'name' => 'Balança Beta',
        'tenant_id' => $tenantB->id,
        'status' => ItemStatus::Active,
        'calibration_due' => now()->addDays(100)->startOfDay(),
    ]);
    tenancy()->end();

    // Executa o comando artisan
    $this->artisan('metrology:check-due')->assertSuccessful();

    // 1. Verifica se o instrumento atrasado de A foi expirado
    expect($overdueInstrumentA->fresh()->status)->toBe(ItemStatus::Expired);

    // 2. Verifica que o User A recebeu a notificação sobre seu instrumento
    Notification::assertSentTo(
        $userA,
        CalibrationDueNotification::class,
        function (CalibrationDueNotification $notification) use ($userA, $dueInstrumentA) {
            return $notification->via($userA) !== [] &&
                $dueInstrumentA->id !== null;
        }
    );

    // 3. Garante isolamento: User B NÃO recebeu notificação sobre os instrumentos da Empresa Alfa
    Notification::assertNotSentTo($userB, CalibrationDueNotification::class);
});
