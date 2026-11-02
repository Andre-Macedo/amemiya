<?php

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\System\Models\User;

beforeEach(function () {
    $tenant = Tenant::create([
        'name' => 'Testing Tenant',
        'slug' => 'testing-notifications',
    ]);
    tenancy()->initialize($tenant);

    $plan = Plan::create([
        'name' => 'Enterprise',
        'slug' => 'enterprise-notifications',
        'price' => 100,
    ]);

    $tenant->subscriptions()->create([
        'plan_id' => $plan->id,
        'name' => 'default',
        'status' => 'active',
        'gateway' => 'manual',
        'gateway_id' => 'man_123',
        'ends_at' => now()->addYear(),
    ]);

    $this->user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    Sanctum::actingAs($this->user);
});

it('can list notifications for the user', function () {
    // Manually insert into notifications table as there is no simple factory for it
    DB::table('notifications')->insert([
        'id' => Str::uuid(),
        'type' => 'TestNotification',
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['message' => 'Hello test']),
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/system/notifications');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('can mark a notification as read', function () {
    $id = Str::uuid();
    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'TestNotification',
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['message' => 'Hello test']),
        'created_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/system/notifications/{$id}/mark-as-read");

    $response->assertStatus(200);
    $this->assertDatabaseHas('notifications', [
        'id' => $id,
        'read_at' => now()->toDateTimeString(),
    ]);
});

it('returns correct unread count', function () {
    DB::table('notifications')->insert([
        ['id' => Str::uuid(), 'type' => 'T1', 'notifiable_type' => $this->user->getMorphClass(), 'notifiable_id' => $this->user->id, 'data' => '{}', 'created_at' => now()],
        ['id' => Str::uuid(), 'type' => 'T2', 'notifiable_type' => $this->user->getMorphClass(), 'notifiable_id' => $this->user->id, 'data' => '{}', 'created_at' => now()],
    ]);

    $response = $this->getJson('/api/v1/system/notifications/unread-count');

    $response->assertStatus(200)
        ->assertJsonPath('count', 2);
});
