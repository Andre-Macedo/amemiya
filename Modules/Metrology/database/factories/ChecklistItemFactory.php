<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class ChecklistItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ChecklistItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'checklist_id' => Checklist::factory(),
            'step' => $this->faker->sentence(),
            'question_type' => 'boolean', // Default, can be overridden
            'order' => $this->faker->numberBetween(1, 10),
            'completed' => $this->faker->boolean(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
