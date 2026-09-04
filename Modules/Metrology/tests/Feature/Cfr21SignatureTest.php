<?php

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Calibration;
use Modules\System\Models\User;

beforeEach(function () {
    $this->password = 'secret123';
    $this->user = User::factory()->create([
        'password' => Hash::make($this->password),
    ]);
    Sanctum::actingAs($this->user);
});

it('blocks calibration approval with incorrect password (CFR 21)', function () {
    $calibration = Calibration::factory()->create(['status' => 'in_review']);

    $response = $this->postJson("/api/v1/metrology/calibrations/{$calibration->id}/approve", [
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Invalid password. Digital signature failed.');

    expect($calibration->refresh()->status)->toBe('in_review');
});

it('allows calibration approval with correct password (CFR 21)', function () {
    $calibration = Calibration::factory()->create(['status' => 'in_review']);

    $response = $this->postJson("/api/v1/metrology/calibrations/{$calibration->id}/approve", [
        'password' => $this->password,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Calibration approved and signed.');

    expect($calibration->refresh()->status)->toBe('published');
});

it('requires a password field for approval', function () {
    $calibration = Calibration::factory()->create(['status' => 'in_review']);

    $response = $this->postJson("/api/v1/metrology/calibrations/{$calibration->id}/approve", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('blocks self-approval when executor tries to approve their own calibration', function () {
    $calibration = Calibration::factory()->create([
        'status' => 'in_review',
        'performed_by_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/metrology/calibrations/{$calibration->id}/approve", [
        'password' => $this->password,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Segregação de funções violada: o técnico responsável pela calibração não pode aprovar seu próprio certificado.');

    expect($calibration->refresh()->status)->toBe('in_review');
});
