<?php

namespace Modules\System\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Models\Station;

class StationFactory extends Factory
{
    protected $model = Station::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city().' Bench',
            'location' => fake()->word(),
        ];
    }
}
