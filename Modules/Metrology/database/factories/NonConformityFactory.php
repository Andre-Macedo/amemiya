<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\NonConformity;
use Modules\System\Models\User;

class NonConformityFactory extends Factory
{
    protected $model = NonConformity::class;

    public function definition(): array
    {
        return [
            'item_id' => Instrument::factory(),
            'item_type' => Instrument::class,
            'user_id' => User::factory(),
            'title' => 'Quality Deviation: '.$this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => 'open',
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
        ];
    }
}
