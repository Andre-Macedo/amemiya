<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\ReferenceStandard;

class InstrumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Metrology\Models\Instrument::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' Instrument',
            'serial_number' => $this->faker->unique()->numerify('INST-#####'),
            'type' => $this->faker->randomElement(['paquimetro', 'micrometro', 'multimetro']),
            'precision' => $this->faker->randomElement(['centesimal', 'milesimal']),
            'location' => $this->faker->city,
            'acquisition_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'calibration_due' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'in_calibration', 'expired']),
        ];
    }
}

