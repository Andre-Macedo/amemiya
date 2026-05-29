<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\WorkOrder;
use Modules\System\Models\User;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'number' => 'OS-'.$this->faker->year().'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'item_id' => Instrument::factory(),
            'item_type' => Instrument::class,
            'status' => $this->faker->randomElement(['received', 'in_queue', 'calibrating', 'finished', 'dispatched']),
            'visual_inspection_notes' => $this->faker->sentence(),
            'customer_notes' => $this->faker->sentence(),
            'expected_return_date' => $this->faker->dateTimeBetween('now', '+15 days'),
            'received_by_id' => User::factory(),
        ];
    }
}
