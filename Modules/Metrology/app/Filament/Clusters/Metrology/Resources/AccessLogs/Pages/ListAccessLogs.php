<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\AccessLogResource;

class ListAccessLogs extends ListRecords
{
    protected static string $resource = AccessLogResource::class;



//    public static function getCluster(): ?string
//    {
//        return static::getResource()::getCluster();
//    }
//
//    public function getSubNavigation(): array
//    {
//        if (filled($cluster = static::getCluster())) {
//            return $this->generateNavigationItems($cluster::getClusteredComponents());
//        }
//
//        return [];
//    }

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
