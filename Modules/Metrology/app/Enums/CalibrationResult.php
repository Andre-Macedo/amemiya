<?php

declare(strict_types=1);

namespace Modules\Metrology\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CalibrationResult: string implements HasLabel, HasColor
{
    case Approved = 'approved';
    case ApprovedWithRestrictions = 'approved_with_restrictions';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Approved => 'Aprovado',
            self::ApprovedWithRestrictions => 'Aprovado com Restrições',
            self::Rejected => 'Reprovado',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Approved => 'success',
            self::ApprovedWithRestrictions => 'warning',
            self::Rejected => 'danger',
        };
    }
}
