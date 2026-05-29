<?php

namespace Modules\System\Filament\Clusters\System\Resources\TenantResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\System\Filament\Clusters\System\Resources\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
