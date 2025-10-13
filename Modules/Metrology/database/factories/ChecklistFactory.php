<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistTemplate;

class ChecklistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Checklist::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'calibration_id' => Calibration::factory(),
            'checklist_template_id' => ChecklistTemplate::factory(),
            'completed' => $this->faker->boolean(),
        ];
    }
}
