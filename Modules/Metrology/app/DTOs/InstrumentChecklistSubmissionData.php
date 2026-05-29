<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class InstrumentChecklistSubmissionData
{
    /**
     * @param  int  $instrumentId  ID do instrumento sendo calibrado.
     * @param  int  $performedById  ID do usuário que realizou a calibração.
     * @param  string  $calibrationDate  Data da calibração (Y-m-d).
     * @param  ChecklistCreationData  $checklistData  Dados do checklist respondido.
     * @param  KitUpdateData|null  $kitUpdateData  Dados de atualização de Kit (se aplicável).
     * @param  float|null  $temperature  Temperatura ambiente (°C).
     * @param  float|null  $humidity  Umidade relativa (%).
     */
    public function __construct(
        public readonly int $instrumentId,
        public readonly int $performedById,
        public readonly string $calibrationDate,
        public readonly ChecklistCreationData $checklistData,
        public readonly ?KitUpdateData $kitUpdateData = null,
        public readonly ?float $temperature = null,
        public readonly ?float $humidity = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            instrumentId: (int) ($data['instrument_id'] ?? 0),
            performedById: (int) ($data['performed_by_id'] ?? 0),
            calibrationDate: (string) ($data['calibration_date'] ?? now()->toDateString()),
            checklistData: ChecklistCreationData::fromArray($data['checklist'] ?? []),
            kitUpdateData: ! empty($data['kit_updates']) ? KitUpdateData::fromArray($data['kit_updates']) : null,
            temperature: isset($data['temperature']) ? (float) $data['temperature'] : null,
            humidity: isset($data['humidity']) ? (float) $data['humidity'] : null,
        );
    }
}
