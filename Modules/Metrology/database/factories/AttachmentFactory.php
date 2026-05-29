<?php

namespace Modules\Metrology\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Metrology\Models\Attachment;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\User;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'attachable_id' => Instrument::factory(),
            'attachable_type' => Instrument::class,
            'file_name' => $this->faker->uuid().'.pdf',
            'original_name' => 'manual.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'file_path' => 'attachments/'.$this->faker->uuid().'.pdf',
            'disk' => 'public',
            'uploaded_by' => User::factory(),
        ];
    }
}
