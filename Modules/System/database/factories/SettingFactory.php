<?php

namespace Modules\System\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Models\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->word(),
        ];
    }
}
