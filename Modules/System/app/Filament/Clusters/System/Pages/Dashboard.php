<?php

namespace Modules\System\Filament\Clusters\System\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Modules\System\Filament\Clusters\System\SystemCluster;

class Dashboard extends BaseDashboard
{
    // 2. Associar esta página ao Cluster System
    protected static ?string $cluster = SystemCluster::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/dashboard';

    protected static ?int $navigationSort = -2; // Ordenar dentro do cluster

    protected static ?string $navigationLabel = 'Dashboard Principal';

    public function getWidgets(): array
    {
        return [
            //
            //
        ];
    }
}
