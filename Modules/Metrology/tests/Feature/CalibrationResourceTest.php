<?php

use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\CreateCalibration;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Resources\CalibrationResource;
use Modules\Metrology\Models\Calibration;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class TestUser extends Authenticatable implements FilamentUser
{
    use HasRoles; 
    
    protected $table = 'users'; // Explicitly point to the real users table
    public $guard_name = 'web'; // Force Spatie to use 'web' guard
    protected $guarded = [];
    
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}

beforeEach(function () {
    // Run Module Migrations
    Artisan::call('module:migrate', ['module' => 'Metrology']);

    // Create User manually via DB
    $id = DB::table('users')->insertGetId([
        'name' => 'Tech Admin',
        'email' => 'admin@metrology.com',
        'password' => '$2y$12$KjG.k4.w.k4.w.k4.w.k4.w.k4.w.k4.w.k4.w.k4.w.k4.w.k4.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->user = TestUser::find($id);
    
    // Assign Super Admin Role
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
});

it('can render create calibration page', function () {
    $this->actingAs($this->user);

    Livewire::test(CreateCalibration::class)
        ->assertStatus(200);
});
