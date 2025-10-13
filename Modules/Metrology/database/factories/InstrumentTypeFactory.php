<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\InstrumentType;

class InstrumentTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InstrumentType::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}
