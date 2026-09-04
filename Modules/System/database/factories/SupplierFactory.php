<?php

namespace Modules\System\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Models\Supplier;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'trade_name' => $this->faker->companySuffix(),
            'cnpj' => $this->faker->unique()->numerify('##############'),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'is_manufacturer' => $this->faker->boolean(),
            'is_calibration_provider' => true,
            'is_maintenance_provider' => $this->faker->boolean(),
            'rbc_code' => 'CAL-'.$this->faker->numerify('####'),
            'accreditation_valid_until' => now()->addYear(),
        ];
    }
}
