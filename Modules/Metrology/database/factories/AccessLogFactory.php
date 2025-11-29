<?php

namespace Modules\Metrology\Database\Factories;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\AccessLog;
use Modules\Metrology\Models\Instrument;

class AccessLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AccessLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'instrument_id' => Instrument::factory(),
            'user_id' => User::factory(),
            'station_id' => Station::factory(),
            'action' => $this->faker->randomElement(['check_in', 'check_out']),
        ];
    }
}
