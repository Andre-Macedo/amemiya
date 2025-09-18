<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
            'type' => $this->faker->randomElement(['bloco_padrao', 'calibrador', 'peso_padrao']),
            'calibration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'calibration_due' => $this->faker->dateTimeBetween('now', '+2 years'),
            'traceability' => $this->faker->randomElement(['INMETRO #123', 'RBC Lab X', 'NIST Traceable']),
            'certificate_path' => $this->faker->optional()->filePath('pdf'),
            'description' => $this->faker->sentence,
        ];
    }
}

