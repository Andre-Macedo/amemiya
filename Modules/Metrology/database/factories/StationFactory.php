<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Station;

class StationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Station::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => 'Station ' . $this->faker->unique()->citySuffix(),
            'location' => $this->faker->secondaryAddress(),
        ];
    }
}
