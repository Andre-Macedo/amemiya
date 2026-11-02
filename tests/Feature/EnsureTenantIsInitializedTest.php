<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureTenantIsInitializedTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Laboratório Metrológico Alfa',
            'slug' => 'lab-alfa',
        ]);

        Route::middleware('tenant.enforce')->get('/test-tenant-guarded-route', function () {
            return response()->json([
                'status' => 'success',
                'tenant_id' => tenancy()->tenant->id,
                'tenant_name' => tenancy()->tenant->name,
            ]);
        });
    }

    public function test_it_aborts_with_400_when_x_tenant_id_header_is_missing_and_tenancy_not_initialized(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $response = $this->getJson('/test-tenant-guarded-route');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'TENANT_NOT_INITIALIZED',
            ]);
    }

    public function test_it_aborts_with_400_when_x_tenant_id_header_points_to_non_existent_tenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $response = $this->withHeader('X-Tenant-ID', 'inexistent-tenant-ulid-99999')
            ->getJson('/test-tenant-guarded-route');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'TENANT_NOT_FOUND',
            ]);
    }

    public function test_it_allows_access_and_initializes_tenancy_when_valid_tenant_ulid_header_is_provided(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/test-tenant-guarded-route');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'tenant_id' => $this->tenant->id,
                'tenant_name' => 'Laboratório Metrológico Alfa',
            ]);

        expect(tenancy()->initialized)->toBeTrue()
            ->and(tenancy()->tenant->id)->toBe($this->tenant->id);
    }

    public function test_it_allows_access_and_initializes_tenancy_when_valid_tenant_slug_header_is_provided(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $response = $this->withHeader('X-Tenant-ID', 'lab-alfa')
            ->getJson('/test-tenant-guarded-route');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'tenant_id' => $this->tenant->id,
                'tenant_name' => 'Laboratório Metrológico Alfa',
            ]);

        expect(tenancy()->initialized)->toBeTrue()
            ->and(tenancy()->tenant->slug)->toBe('lab-alfa');
    }

    public function test_it_allows_access_if_tenancy_was_already_initialized_and_no_header_is_passed(): void
    {
        tenancy()->initialize($this->tenant);

        $response = $this->getJson('/test-tenant-guarded-route');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'tenant_id' => $this->tenant->id,
            ]);
    }
}
