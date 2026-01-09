<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Modules\Metrology\Models\InstrumentType;

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
            'name' => $this->faker->word . ' ' . $this->faker->numberBetween(1, 100),
            'stock_number' => $this->faker->unique()->bothify('??-###'),
            'serial_number' => $this->faker->unique()->bothify('SRL-#####'),
            'instrument_type_id' => InstrumentType::factory(),
            'mpe' => $this->faker->randomElement(['0.01', '0.02', '0.05', '0.001']),
            'manufacturer' => $this->faker->company(),
            'measuring_range' => $this->faker->randomElement(['0-150mm', '0-25mm', '25-50mm', '0-12.7mm']),
            'resolution' => $this->faker->randomElement(['0.01mm', '0.001mm']),
            'location' => $this->faker->city,
            'acquisition_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'calibration_due' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(\Modules\Metrology\Enums\ItemStatus::cases())->value,
            'image_path' => null,
        ];
    }
}
