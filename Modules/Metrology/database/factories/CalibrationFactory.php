<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\System\Models\User;

/**
 * Factory para criação de registros de Calibração.
 *
 * @extends Factory<Calibration>
 */
class CalibrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Calibration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $calibratable = $this->faker->randomElement([
            Instrument::class,
            ReferenceStandard::class,
        ]);

        return [
            'calibrated_item_id' => $calibratable::factory(),
            'calibrated_item_type' => $calibratable,
            'calibration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'type' => $this->faker->randomElement(['internal', 'external_rbc']),
            'result' => $this->faker->randomElement(CalibrationResult::cases())->value,
            'deviation' => $this->faker->randomFloat(4, -0.1, 0.1),
            'uncertainty' => $this->faker->randomFloat(4, 0, 0.05),
            'notes' => $this->faker->sentence,
            'certificate_path' => $this->faker->optional()->filePath('pdf'),
            'performed_by_id' => User::factory(),
        ];
    }
}
