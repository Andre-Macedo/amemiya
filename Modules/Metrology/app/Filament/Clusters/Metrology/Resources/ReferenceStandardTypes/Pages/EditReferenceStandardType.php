<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\ReferenceStandardTypeResource;

class EditReferenceStandardType extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
