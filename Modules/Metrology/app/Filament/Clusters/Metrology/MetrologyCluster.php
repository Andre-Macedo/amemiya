<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

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
