<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;

class ViewCalibration extends ViewRecord
{
    protected static string $resource = CalibrationResource::class;

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
