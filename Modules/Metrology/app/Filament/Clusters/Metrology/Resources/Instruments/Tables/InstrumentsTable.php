<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Tables;

use App\Models\Station;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Modules\Metrology\Models\Instrument;

class InstrumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->label('Número de Série')
                    ->searchable(),
                TextColumn::make('instrumentType.name')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Localização')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('calibration_due')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->calibration_due < now() ? 'danger' : null)
                    ->icon(fn ($record) => $record->calibration_due < now() ? 'heroicon-m-exclamation-circle' : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'in_calibration' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'in_calibration' => 'Em Calibração',
                        'expired' => 'Vencido',
                    ]),
                SelectFilter::make('instrument_type_id')
                    ->label('Tipo de Instrumento')
                    ->relationship('instrumentType', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),

            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

// ---------------------------------------------------------------
                    // 1. ENVIAR PARA CALIBRAÇÃO (Fluxo Normal)
                    // ---------------------------------------------------------------
                    Action::make('send_calibration')
                        ->label('Enviar p/ Calibrar')
                        ->icon('heroicon-o-beaker') // Ícone de laboratório
                        ->color('info')
                        // Visível se estiver Ativo, Vencido ou se acabou de ser Reprovado (e decidiram re-testar)
                        ->visible(fn (Instrument $record) => in_array($record->status, ['active', 'expired']))
                        ->form([
                            \Filament\Forms\Components\Select::make('type')
                                ->label('Onde será realizada?')
                                ->options([
                                    'internal' => 'Interna (Laboratório Próprio)',
                                    'external' => 'Externa (Fornecedor/RBC)',
                                ])
                                ->default('internal')
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('station_id', null);
                                    $set('supplier_id', null);
                                })
                                ->required(),

                            // Lógica de Estação Interna
                            Select::make('station_id')
                                ->label('Laboratório Interno')
                                ->options(\App\Models\Station::where('type', 'internal_lab')->pluck('name', 'id'))
                                ->required(fn (Get $get) => $get('type') === 'internal')
                                ->visible(fn (Get $get) => $get('type') === 'internal'),

                            // Lógica de Fornecedor Externo
                            Select::make('supplier_id')
                                ->label('Fornecedor / Laboratório Externo')
                                ->options(\App\Models\Supplier::where('is_calibration_provider', true)->pluck('name', 'id'))
                                ->required(fn (Get $get) => $get('type') === 'external')
                                ->visible(fn (Get $get) => $get('type') === 'external')
                                ->searchable(),

                            Textarea::make('notes')->label('Observações de Envio'),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            $updateData = ['status' => 'in_calibration'];

                            if ($data['type'] === 'internal') {
                                $updateData['current_station_id'] = $data['station_id'];
                                $updateData['current_supplier_id'] = null;
                            } else {
                                $externalStation = \App\Models\Station::where('type', 'external_provider')->first();
                                $updateData['current_station_id'] = $externalStation?->id;
                                $updateData['current_supplier_id'] = $data['supplier_id'];
                            }

                            $record->update($updateData);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Enviado para Calibração')
                                ->body("O instrumento foi movido para o fluxo de calibração.")
                                ->send();
                        }),

                    // ---------------------------------------------------------------
                    // 2. ENVIAR PARA MANUTENÇÃO (Se quebrou ou reprovou)
                    // ---------------------------------------------------------------
                    Action::make('send_maintenance')
                        ->label('Enviar p/ Manutenção')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->requiresConfirmation()
                        // Visível se estiver Ativo (quebrou na linha) ou Reprovado (falhou no teste)
                        ->visible(fn (Instrument $record) => in_array($record->status, ['active', 'rejected', 'expired']))
                        ->form([
                            \Filament\Forms\Components\Textarea::make('problem_description')
                                ->label('Descrição do Problema')
                                ->required(),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            // Aqui você poderia mover para uma estação "Oficina" se tiver
                            $record->update([
                                'status' => 'maintenance',
                                // 'current_station_id' => ... (opcional)
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Enviado para Manutenção')
                                ->send();
                        }),

                    // Ação: Retorno da Manutenção (Libera para Calibrar)
                    Action::make('return_maintenance')
                        ->label('Retornou (Calibrar)')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn (Instrument $record) => $record->status === 'maintenance')
                        ->form([
                            TextInput::make('repair_notes')->label('O que foi feito?')->required(),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            // Aqui você poderia salvar um log de manutenção em outra tabela
                            $record->update(['status' => 'in_calibration']);
                        }),

                    // Ação: Descarte
                    Action::make('scrap')
                        ->label('Descartar (Sucata)')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (Instrument $record) => in_array($record->status, ['rejected', 'maintenance']))
                        ->requiresConfirmation()
                        ->action(fn (Instrument $record) => $record->update(['status' => 'scrapped'])),
                ])

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
