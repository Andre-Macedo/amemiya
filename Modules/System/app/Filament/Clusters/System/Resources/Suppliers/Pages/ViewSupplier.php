<?php

namespace Modules\System\Filament\Clusters\System\Resources\Suppliers\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\SupplierResource;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
