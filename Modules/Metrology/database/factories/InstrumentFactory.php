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
        // Garante que o diretório de imagens exista
        Storage::disk('public')->makeDirectory('instrument_images');

        return [
            'name' => $this->faker->word . ' ' . $this->faker->numberBetween(1, 100),
            'stock_number' => $this->faker->unique()->numerify('INST-#####'),
            'serial_number' => $this->faker->unique()->numerify('SRL-#####'),
            'instrument_type_id' => InstrumentType::factory(),
            'precision' => $this->faker->randomElement(['centesimal', 'milesimal']),
            'location' => $this->faker->city,
            'acquisition_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'calibration_due' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'in_calibration', 'expired']),
            'image_path' => 'instrument_images/' . $this->faker->image(storage_path('app/public/instrument_images'), 640, 480, null, false),
        ];
    }
}
