<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\System\Models\Setting;
use Modules\System\Models\User;

beforeEach(function () {
    Storage::fake('local');
    $this->tenant = Tenant::create([
        'name' => 'Testing Tenant',
        'slug' => 'testing',
    ]);
    tenancy()->initialize($this->tenant);

    $plan = Plan::create([
        'name' => 'Enterprise',
        'slug' => 'enterprise',
        'price' => 100,
    ]);

    $this->tenant->subscriptions()->create([
        'plan_id' => $plan->id,
        'name' => 'default',
        'status' => 'active',
        'gateway' => 'manual',
        'gateway_id' => 'man_123',
        'ends_at' => now()->addYear(),
    ]);

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

function getOpensslConfig(): array
{
    $candidates = [
        getenv('OPENSSL_CONF'),
        'C:\\Users\\andrl\\.config\\herd\\bin\\php82\\extras\\ssl\\openssl.cnf',
        'C:\\Users\\andrl\\.config\\herd\\bin\\php83\\extras\\ssl\\openssl.cnf',
        'C:\\Users\\andrl\\.config\\herd\\openssl.cnf',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate && file_exists($candidate)) {
            return ['config' => $candidate];
        }
    }

    return [];
}

function generateTestP12(string $password = 'secret123', int $days = 365): string
{
    $config = getOpensslConfig();

    $dn = [
        'countryName' => 'BR',
        'stateOrProvinceName' => 'SP',
        'localityName' => 'Sao Paulo',
        'organizationName' => 'Lab Test Metrologia',
        'commonName' => 'Lab Test Metrologia LTDA:12345678000199',
    ];
    $privkey = openssl_pkey_new(array_merge([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ], $config));

    $csr = openssl_csr_new($dn, $privkey, array_merge(['digest_alg' => 'sha256'], $config));
    $x509 = openssl_csr_sign($csr, null, $privkey, $days, array_merge(['digest_alg' => 'sha256'], $config));
    $p12 = '';
    openssl_pkcs12_export($x509, $p12, $privkey, $password);

    return $p12;
}

it('returns unconfigured status when no digital certificate is set', function () {
    $response = $this->getJson('/api/v1/system/certificate');

    $response->assertStatus(200)
        ->assertJson([
            'configured' => false,
            'is_valid' => false,
            'common_name' => null,
        ]);
});

it('rejects certificate upload with invalid password', function () {
    $p12Content = generateTestP12('correct_pass');
    $file = UploadedFile::fake()->createWithContent('cert.pfx', $p12Content);

    $response = $this->postJson('/api/v1/system/certificate', [
        'certificate' => $file,
        'password' => 'wrong_pass',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('uploads, validates and securely persists a valid pfx certificate', function () {
    $p12Content = generateTestP12('secure_pass_123', 365);
    $file = UploadedFile::fake()->createWithContent('cert.pfx', $p12Content);

    $response = $this->postJson('/api/v1/system/certificate', [
        'certificate' => $file,
        'password' => 'secure_pass_123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('certificate.configured', true)
        ->assertJsonPath('certificate.is_valid', true)
        ->assertJsonPath('certificate.common_name', 'Lab Test Metrologia LTDA:12345678000199');

    // Verifica se os settings foram gravados
    expect(Setting::getValue('lab_certificate_path'))->not->toBeNull()
        ->and(Setting::getValue('lab_certificate_common_name'))->toBe('Lab Test Metrologia LTDA:12345678000199');

    // Verifica se a senha foi salva criptografada
    $encryptedPass = Setting::getValue('lab_certificate_password');
    expect($encryptedPass)->not->toBe('secure_pass_123')
        ->and(Crypt::decryptString($encryptedPass))->toBe('secure_pass_123');

    // Verifica se o arquivo .pem existe no storage seguro
    $path = Setting::getValue('lab_certificate_path');
    expect(Storage::disk('local')->exists($path))->toBeTrue();
});

it('can delete the configured certificate and clean settings', function () {
    $p12Content = generateTestP12('delete_pass', 365);
    $file = UploadedFile::fake()->createWithContent('cert.pfx', $p12Content);

    $this->postJson('/api/v1/system/certificate', [
        'certificate' => $file,
        'password' => 'delete_pass',
    ])->assertStatus(200);

    $deleteResponse = $this->deleteJson('/api/v1/system/certificate');
    $deleteResponse->assertStatus(200);

    expect(Setting::getValue('lab_certificate_path'))->toBeNull()
        ->and(Setting::getValue('lab_certificate_password'))->toBeNull();

    $statusResponse = $this->getJson('/api/v1/system/certificate');
    $statusResponse->assertStatus(200)
        ->assertJsonPath('configured', false);
});
