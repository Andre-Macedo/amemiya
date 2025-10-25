<?php

namespace App\Filament\Clusters\System\Pages;

use App\Filament\Clusters\System\SystemCluster;
use Filament\Pages\Dashboard as BaseDashboard;

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
