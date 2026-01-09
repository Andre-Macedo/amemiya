<?php

declare(strict_types=1);

namespace Modules\Metrology\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ItemStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case InCalibration = 'in_calibration';
    case Rejected = 'rejected';
    case Lost = 'lost';
    case Expired = 'expired';
    case Scrapped = 'scrapped';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Maintenance => 'Em Manutenção',
            self::InCalibration => 'Em Calibração',
            self::Rejected => 'Reprovado',
            self::Lost => 'Perdido',
            self::Expired => 'Vencido',
            self::Scrapped => 'Sucata',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Maintenance => 'warning',
            self::InCalibration => 'info',
            self::Rejected => 'danger',
            self::Lost => 'danger',
            self::Expired => 'danger',
            self::Scrapped => 'gray',
        };
    }
}
