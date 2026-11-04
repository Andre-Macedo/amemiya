<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\System\Models\User;
use Tests\TestCase;

class EnforceSessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'tech@amemiya.test',
        ]);

        Route::middleware('web')->group(function () {
            Route::get('/test-session-protected', function () {
                return response()->json([
                    'status' => 'authenticated',
                    'user_id' => Auth::id(),
                ]);
            });
        });
    }

    public function test_it_allows_active_session_and_updates_activity_timestamp(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['last_activity_time' => Carbon::now()->subMinutes(5)->timestamp])
            ->getJson('/test-session-protected');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'authenticated',
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull(session('last_activity_time'));
    }

    public function test_it_terminates_idle_session_exceeding_timeout_limit(): void
    {
        config(['session.inactivity_timeout' => 30]);

        $response = $this->actingAs($this->user)
            ->withSession(['last_activity_time' => Carbon::now()->subMinutes(35)->timestamp])
            ->getJson('/test-session-protected');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'SESSION_EXPIRED',
        ]);

        $this->assertFalse(Auth::check());
    }
}
