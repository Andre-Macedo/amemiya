<?php

namespace Modules\Metrology\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class CalibrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Metrology\Models\Calibration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'instrument_id' => Instrument::factory(),
            'calibration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'type' => $this->faker->randomElement(['internal', 'external_rbc']),
            'result' => $this->faker->randomElement(['approved', 'rejected']),
            'deviation' => $this->faker->randomFloat(4, -0.1, 0.1),
            'uncertainty' => $this->faker->randomFloat(4, 0, 0.05),
            'notes' => $this->faker->sentence,
            'certificate_path' => $this->faker->optional()->filePath('pdf'),
            'performed_by' => User::factory(),
        ];
    }

    public function withReferenceStandards(int $count = 2): self
    {
        return $this->afterCreating(function (Calibration $calibration) use ($count) {
            $standards = ReferenceStandard::factory($count)->create();
            $calibration->referenceStandards()->attach($standards->pluck('id'));
        });
    }

}

