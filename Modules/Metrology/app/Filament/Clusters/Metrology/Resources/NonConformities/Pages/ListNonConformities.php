<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformityResource;

class ListNonConformities extends ListRecords
{
    protected static string $resource = NonConformityResource::class;
}
