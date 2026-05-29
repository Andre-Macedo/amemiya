<?php

declare(strict_types=1);

namespace Modules\Metrology\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Enum que representa o resultado final de uma calibração.
 */
enum CalibrationResult: string implements HasColor, HasLabel
{
    case Approved = 'approved';
    case ApprovedWithRestrictions = 'approved_with_restrictions';
    case Conditional = 'conditional';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Approved => 'Aprovado',
            self::ApprovedWithRestrictions => 'Aprovado com Restrições',
            self::Conditional => 'Aprovado Condicional (Banda de Guarda)',
            self::Rejected => 'Reprovado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Approved => 'success',
            self::ApprovedWithRestrictions => 'warning',
            self::Conditional => 'info',
            self::Rejected => 'danger',
        };
    }
}
