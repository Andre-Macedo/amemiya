<?php

namespace App\Filament\Concerns;

use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;
use Filament\Resources\Pages\Concerns\InteractsWithParentRecord;
use Filament\Resources\Pages\Page;

trait InteractsWithCluster
{
    public static function getCluster(): ?string
    {
        if (! is_subclass_of(static::class, Page::class)) {
            return null;
        }

        $resource = static::getResource();

        return $resource::getCluster();
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }
}
