<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\AccessLogResource;

class CreateAccessLog extends CreateRecord
{
    protected static string $resource = AccessLogResource::class;
    public static function getCluster(): ?string
    {
        return MetrologyCluster::class;
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }
}
