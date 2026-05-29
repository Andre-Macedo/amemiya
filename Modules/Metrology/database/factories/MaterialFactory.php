<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Material;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().' Steel',
            'cte' => $this->faker->randomFloat(2, 10, 25),
            'category' => 'Metal',
        ];
    }
}
