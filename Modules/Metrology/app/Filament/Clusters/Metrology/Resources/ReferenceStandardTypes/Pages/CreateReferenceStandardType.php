<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\ReferenceStandardTypeResource;

class CreateReferenceStandardType extends CreateRecord
{
    use InteractsWithCluster;

    protected static string $resource = ReferenceStandardTypeResource::class;

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
