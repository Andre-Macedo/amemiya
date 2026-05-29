<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\AccessLog;
use Modules\System\Models\Station;
use Modules\System\Models\User;

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
