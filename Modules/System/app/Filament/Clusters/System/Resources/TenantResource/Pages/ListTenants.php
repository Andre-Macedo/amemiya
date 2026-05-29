<?php

namespace Modules\System\Filament\Clusters\System\Resources\TenantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\Clusters\System\Resources\TenantResource;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
