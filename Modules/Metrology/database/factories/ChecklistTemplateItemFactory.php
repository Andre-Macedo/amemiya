<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\ChecklistTemplateItem;
use Modules\Metrology\Models\ChecklistTemplate;

class ChecklistTemplateItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ChecklistTemplateItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'checklist_template_id' => ChecklistTemplate::factory(),
            'step' => $this->faker->sentence(3),
            'question_type' => $this->faker->randomElement(['yes_no', 'value', 'text']),
            'order' => $this->faker->numberBetween(1, 100),
            'required_readings' => 1,
            'reference_standard_type_id' => null,
        ];
    }
}
