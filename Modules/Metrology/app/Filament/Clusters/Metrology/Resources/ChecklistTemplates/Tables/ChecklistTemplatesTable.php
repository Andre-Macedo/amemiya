<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Metrology\Models\ChecklistTemplate;

class ChecklistTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Procedimento')
                    ->description(fn (ChecklistTemplate $record) => $record->revision_notes)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version')
                    ->label('Ver.')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-archive-box')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('instrumentType.name')
                    ->label('Tipo de Instrumento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Passos')
                    ->counts('items'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Apenas Ativos')
                    ->default(true),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('revise')
                    ->label('Revisar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->hidden(fn (ChecklistTemplate $record) => ! $record->is_active)
                    ->modalHeading('Criar Nova Revisão')
                    ->modalDescription('Esta ação desativará a versão atual e criará uma nova cópia editável. Deseja prosseguir?')
                    ->form([
                        Textarea::make('revision_notes')
                            ->label('Notas da Revisão')
                            ->placeholder('Ex: Atualizado critério conforme nova norma ISO...')
                            ->required(),
                    ])
                    ->action(function (ChecklistTemplate $record, array $data) {
                        $newVersion = $record->createRevision($data['revision_notes']);

                        Notification::make()
                            ->title('Nova revisão criada!')
                            ->body("A versão {$newVersion->version} agora está ativa.")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn (ChecklistTemplate $record) => ! $record->is_active), // Impede editar versões obsoletas
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
