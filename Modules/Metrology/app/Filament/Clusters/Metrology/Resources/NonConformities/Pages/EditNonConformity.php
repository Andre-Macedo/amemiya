<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Actions\CloseNonConformityAction;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformityResource;

class EditNonConformity extends EditRecord
{
    protected static string $resource = NonConformityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close_rnc')
                ->label('Fechar RNC')
                ->color('success')
                ->icon('heroicon-m-check-badge')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'closed')
                ->action(function (CloseNonConformityAction $action) {
                    try {
                        $action->execute($this->getRecord());

                        Notification::make()
                            ->title('RNC Fechada com Sucesso')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'closed_at', 'closed_by']);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Erro ao Fechar RNC')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
