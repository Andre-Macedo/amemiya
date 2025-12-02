<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Pages;

use Filament\Pages\Page;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Widgets\ComplianceRateWidget;
use Modules\Metrology\Filament\Widgets\OverdueCalibrationsWidget;
use Modules\Metrology\Filament\Widgets\RecentCalibrationsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $title = 'Metrology Dashboard';

    protected static ?int $navigationSort = -1;
    protected static string $routePath = '/dashboard';

    public function getWidgets(): array
    {
        return [
//            OverdueCalibrationsWidget::class,
//            RecentCalibrationsWidget::class,
//            ComplianceRateWidget::class,
        ];
    }

}
