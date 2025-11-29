<?php

namespace App\Filament\Clusters\System\Resources\Stations\Pages;

use App\Filament\Clusters\System\Resources\Stations\StationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;

class ViewStation extends ViewRecord
{
    protected static string $resource = StationResource::class;

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

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
