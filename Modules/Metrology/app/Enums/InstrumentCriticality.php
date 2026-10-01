<?php

declare(strict_types=1);

namespace Modules\Metrology\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InstrumentCriticality: string implements HasColor, HasIcon, HasLabel
{
    case SafetyNr12 = 'safety_nr12';
    case SafetyNr13 = 'safety_nr13';
    case ProductQualityCtq = 'product_quality_ctq';
    case Environmental = 'environmental';
    case OperationalReference = 'operational_reference';

    public function getLabel(): string
    {
        return match ($this) {
            self::SafetyNr12 => 'Segurança de Máquinas (NR-12)',
            self::SafetyNr13 => 'Vasos de Pressão / Caldeiras (NR-13)',
            self::ProductQualityCtq => 'Crítico para Qualidade (CTQ / IATF)',
            self::Environmental => 'Meio Ambiente (ISO 14001)',
            self::OperationalReference => 'Operacional / Referência',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::SafetyNr12 => 'NR-12',
            self::SafetyNr13 => 'NR-13',
            self::ProductQualityCtq => 'CTQ',
            self::Environmental => 'Ambiental',
            self::OperationalReference => 'Operacional',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SafetyNr12, self::SafetyNr13 => 'danger',
            self::ProductQualityCtq => 'warning',
            self::Environmental => 'info',
            self::OperationalReference => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SafetyNr12, self::SafetyNr13 => 'heroicon-m-shield-exclamation',
            self::ProductQualityCtq => 'heroicon-m-check-badge',
            self::Environmental => 'heroicon-m-globe-alt',
            self::OperationalReference => 'heroicon-m-wrench',
        };
    }

    public function isSafetyOrQualityCritical(): bool
    {
        return in_array($this, [
            self::SafetyNr12,
            self::SafetyNr13,
            self::ProductQualityCtq,
        ], true);
    }
}
