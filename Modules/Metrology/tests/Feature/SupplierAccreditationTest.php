<?php

namespace Modules\Metrology\Tests\Feature;

use App\Models\Supplier;
use Tests\Concerns\HasSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Filament\Clusters\System\Resources\Suppliers\Pages\EditSupplier;

uses(RefreshDatabase::class, HasSuperAdmin::class);

test('it uploads accreditation certificate for supplier', function () {
    Storage::fake('public');
    $user = $this->createSuperAdmin();
    $supplier = Supplier::factory()->create(['is_calibration_provider' => true]);
    
    $file = UploadedFile::fake()->create('accreditation.pdf', 100, 'application/pdf');

    Livewire::test(EditSupplier::class, ['record' => $supplier->getRouteKey()])
        ->fillForm([
            'accreditation_certificate' => $file,
            'accreditation_valid_until' => now()->addYear()->format('Y-m-d'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $supplier->refresh();
    
    expect($supplier->accreditation_certificate)->not->toBeNull()
        ->and($supplier->accreditation_valid_until)->not->toBeNull();
        
    Storage::disk('public')->assertExists($supplier->accreditation_certificate);
});
