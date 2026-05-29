<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology;

use BackedEnum;
use Filament\Clusters\Cluster;

/**
 * Cluster principal do módulo de Metrologia.
 *
 * Agrupa todos os recursos (Instrumentos, Calibrações, Padrões, etc.) sob a navegação "Metrologia".
 */
class MetrologyCluster extends Cluster
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Metrologia';

    protected static ?string $slug = 'metrology';

    public static function getPages(): array
    {
        return [
            'index' => Pages\Dashboard::route('/'),
        ];
    }
}
