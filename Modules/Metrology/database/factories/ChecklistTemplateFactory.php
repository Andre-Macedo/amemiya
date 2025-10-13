<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\InstrumentType;

class ChecklistTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ChecklistTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => 'Checklist ' . $this->faker->words(2, true),
            'instrument_type_id' => InstrumentType::factory(),
        ];
    }
}
