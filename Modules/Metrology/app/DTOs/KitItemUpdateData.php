<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

/**
 * Data Transfer Object for updating individual kit items during calibration.
 */
class KitItemUpdateData
{
    /**
     * @param  string  $childId  ID do padrão filho (componente do kit).
     * @param  float  $newActualValue  Novo valor verdadeiro medido.
     */
    public function __construct(
        public readonly string $childId,
        public readonly float $newActualValue,
    ) {}

    /**
     * Creates an instance from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            childId: (string) ($data['child_id'] ?? ''),
            newActualValue: (float) ($data['new_actual_value'] ?? 0.0),
        );
    }
}
