<?php

namespace Modules\Metrology\DTOs;

class InstrumentChecklistSubmissionData
{
    public function __construct(
        public int $instrument_id,
        public int $checklist_template_id,
        public string $result,
        public array $items,
        public ?float $temperature = null,
        public ?float $humidity = null,
        public ?float $uncertainty = null,
        public ?float $deviation = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            instrument_id: (int) $data['instrument_id'],
            checklist_template_id: (int) $data['checklist_template_id'],
            result: $data['result'],
            items: $data['items'] ?? [],
            temperature: isset($data['temperature']) ? (float) $data['temperature'] : null,
            humidity: isset($data['humidity']) ? (float) $data['humidity'] : null,
            uncertainty: isset($data['uncertainty']) ? (float) $data['uncertainty'] : null,
            deviation: isset($data['deviation']) ? (float) $data['deviation'] : null,
        );
    }
}
