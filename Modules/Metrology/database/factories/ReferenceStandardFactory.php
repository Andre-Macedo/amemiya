<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\ReferenceStandardType;

class ReferenceStandardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Metrology\Models\ReferenceStandard::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Standard',
            'serial_number' => $this->faker->unique()->numerify('STD-#####'),
            'reference_standard_type_id' => ReferenceStandardType::factory(),
            'calibration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'calibration_due' => $this->faker->dateTimeBetween('now', '+2 years'),
            'traceability' => $this->faker->randomElement(['INMETRO #123', 'RBC Lab X', 'NIST Traceable']),
            'certificate_path' => null, // Normalmente gerado em outro processo
            'description' => $this->faker->sentence,
        ];
    }
}
