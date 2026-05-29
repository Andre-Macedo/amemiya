<?php

namespace Modules\System\Filament\Clusters\System\Resources\SupportTicketResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\System\Filament\Clusters\System\Resources\SupportTicketResource;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
