<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\System\Models\User;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 199.00,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Acme Metrology Lab',
            'slug' => 'acme-metrology-lab',
            'plan_id' => $plan->id,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'name' => 'default',
            'gateway' => 'manual',
            'status' => 'active',
            'ends_at' => now()->addYear(),
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'auditor@amemiya.test',
            'password' => Hash::make('password123'),
        ]);

        $this->google2fa = new Google2FA;
    }

    public function test_user_can_initialize_2fa_setup(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/system/2fa/setup');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'secret',
            'qr_code_url',
            'qr_code_svg',
            'recovery_codes',
        ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
        $this->assertNull($this->user->two_factor_confirmed_at);
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    }

    public function test_user_can_confirm_2fa_activation_with_valid_totp(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = $secret;
        $this->user->save();

        Sanctum::actingAs($this->user);

        $validOtp = $this->google2fa->getCurrentOtp($secret);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/system/2fa/confirm', [
                'code' => $validOtp,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'enabled',
        ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_confirmed_at);
        $this->assertTrue($this->user->hasTwoFactorEnabled());
    }

    public function test_user_cannot_confirm_2fa_with_invalid_totp(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = $secret;
        $this->user->save();

        Sanctum::actingAs($this->user);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/system/2fa/confirm', [
                'code' => '000000',
            ]);

        $response->assertStatus(422);

        $this->user->refresh();
        $this->assertNull($this->user->two_factor_confirmed_at);
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    }

    public function test_login_returns_2fa_challenge_when_user_has_2fa_active(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = $secret;
        $this->user->two_factor_confirmed_at = now();
        $this->user->save();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'auditor@amemiya.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'requires_2fa' => true,
        ]);
        $response->assertJsonStructure([
            'requires_2fa',
            'two_factor_token',
            'message',
        ]);
        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_user_can_complete_2fa_login_with_valid_totp(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = $secret;
        $this->user->two_factor_confirmed_at = now();
        $this->user->save();

        $challengeToken = $this->user->createToken('2fa_challenge', ['2fa:pending'])->plainTextToken;
        $validOtp = $this->google2fa->getCurrentOtp($secret);

        $response = $this->postJson('/api/v1/login/2fa', [
            'two_factor_token' => $challengeToken,
            'code' => $validOtp,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'email', 'name'],
        ]);
    }

    public function test_user_can_complete_2fa_login_with_recovery_code(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $recoveryCode = 'RECOVERY-CODE-12345';
        $this->user->two_factor_secret = $secret;
        $this->user->two_factor_recovery_codes = [$recoveryCode, 'RECOVERY-CODE-67890'];
        $this->user->two_factor_confirmed_at = now();
        $this->user->save();

        $challengeToken = $this->user->createToken('2fa_challenge', ['2fa:pending'])->plainTextToken;

        $response = $this->postJson('/api/v1/login/2fa', [
            'two_factor_token' => $challengeToken,
            'code' => $recoveryCode,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);

        $this->user->refresh();
        $this->assertNotContains($recoveryCode, $this->user->two_factor_recovery_codes);
        $this->assertCount(1, $this->user->two_factor_recovery_codes);
    }

    public function test_user_can_disable_2fa_providing_current_password(): void
    {
        $this->user->two_factor_secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_recovery_codes = ['CODE1', 'CODE2'];
        $this->user->two_factor_confirmed_at = now();
        $this->user->save();

        Sanctum::actingAs($this->user);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/system/2fa/disable', [
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'disabled',
        ]);

        $this->user->refresh();
        $this->assertNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_recovery_codes);
        $this->assertNull($this->user->two_factor_confirmed_at);
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    }
}
