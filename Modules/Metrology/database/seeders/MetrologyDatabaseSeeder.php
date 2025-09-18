<?php

namespace Modules\Metrology\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class MetrologyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 reference standards
        $referenceStandards = ReferenceStandard::factory()->count(5)->create();

        // Create 10 instruments, each linked to a random reference standard
        $instruments = Instrument::factory()->count(10)->create();

        // Create 20 calibrations, each linked to an instrument and 1-3 reference standards
        $calibrations = Calibration::factory()->count(20)
            ->create([
                'instrument_id' => $instruments->random()->id,
                'performed_by' => User::factory()->create()->id,
            ])
            ->each(function (Calibration $calibration) use ($referenceStandards) {
                $calibration->referenceStandards()->attach(
                    $referenceStandards->random(random_int(1, 3))->pluck('id')->toArray()
                );
            });
    }
}
