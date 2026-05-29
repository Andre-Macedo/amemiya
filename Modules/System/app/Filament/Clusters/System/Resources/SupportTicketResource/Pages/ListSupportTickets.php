<?php

namespace Modules\System\Filament\Clusters\System\Resources\SupportTicketResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\Clusters\System\Resources\SupportTicketResource;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
