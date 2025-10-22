<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\ReferenceStandardType;

class ReferenceStandardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Metrology\Models\ReferenceStandard::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Padrão',
            'serial_number' => $this->faker->unique()->numerify('SN-######'),
            'asset_tag' => $this->faker->unique()->numerify('PAT-#####'),
            'reference_standard_type_id' => ReferenceStandardType::factory(),
            'description' => $this->faker->sentence,        ];
    }
}
