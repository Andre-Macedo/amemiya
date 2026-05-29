<?php

namespace Modules\IoT\Filament\Clusters\IoT;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class IoTCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

    protected static ?string $navigationLabel = 'Internet das Coisas';

    protected static ?string $slug = 'iot';

    protected static ?int $navigationSort = 10;

    public static function getClusteredNavigationLabel(): string
    {
        return 'IoT';
    }
}
