<?php

declare(strict_types=1);

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
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(\Modules\Metrology\Enums\ItemStatus::class),
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
                    // ---------------------------------------------------------------
                    // 1. REGISTRAR MOVIMENTAÇÃO (Custódia) - NOVO RECURSO
                    // ---------------------------------------------------------------
                    Action::make('register_movement')
                        ->label('Registrar Movimentação')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('primary')
                        ->form([
                            Select::make('station_id')
                                ->label('Destino (Setor/Estação)')
                                ->options(Station::pluck('name', 'id')) // Mostra todas as estações
                                ->required()
                                ->searchable(),
                            Textarea::make('notes')
                                ->label('Motivo / Justificativa')
                                ->required(),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            $station = Station::find($data['station_id']);

                            // 1. Atualizar Localização Atual
                            $record->update([
                                'current_station_id' => $station->id,
                                'location' => $station->name, // Mantém o texto simples syncado
                            ]);

                            // 2. Criar Log de Acesso (Histórico)
                            \Modules\Metrology\Models\AccessLog::create([
                                'instrument_id' => $record->id,
                                'user_id' => auth()->id(),
                                'station_id' => $station->id,
                                'action' => 'moved: ' . ($data['notes'] ?? 'Sem justificativa'),
                            ]);

                            Notification::make()->success()->title('Movimentação Registrada')->send();
                        }),

                    // ---------------------------------------------------------------
                    // 2. ENVIAR PARA CALIBRAÇÃO (Fluxo Normal)
                    // ---------------------------------------------------------------
                    Action::make('send_calibration')
                        ->label('Enviar p/ Calibrar')
                        ->icon('heroicon-o-beaker')
                        ->color('info')
                        ->visible(fn (Instrument $record) => in_array($record->status, [\Modules\Metrology\Enums\ItemStatus::Active, \Modules\Metrology\Enums\ItemStatus::Expired]))
                        ->form([
                            Select::make('type')
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

                            Select::make('station_id')
                                ->label('Laboratório Interno')
                                ->options(Station::where('type', 'internal_lab')->pluck('name', 'id'))
                                ->required(fn (Get $get) => $get('type') === 'internal')
                                ->visible(fn (Get $get) => $get('type') === 'internal'),

                            Select::make('supplier_id')
                                ->label('Fornecedor / Laboratório Externo')
                                ->options(\App\Models\Supplier::where('is_calibration_provider', true)->pluck('name', 'id'))
                                ->required(fn (Get $get) => $get('type') === 'external')
                                ->visible(fn (Get $get) => $get('type') === 'external')
                                ->searchable(),

                            Textarea::make('notes')->label('Observações de Envio'),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            $updateData = ['status' => \Modules\Metrology\Enums\ItemStatus::InCalibration];
                            $logAction = 'sent_to_calibration';
                            $destinationStationId = null;

                            if ($data['type'] === 'internal') {
                                $updateData['current_station_id'] = $data['station_id'];
                                $updateData['current_supplier_id'] = null;
                                $destinationStationId = $data['station_id'];
                            } else {
                                $externalStation = Station::where('type', 'external_provider')->first();
                                $updateData['current_station_id'] = $externalStation?->id;
                                $updateData['current_supplier_id'] = $data['supplier_id'];
                                $destinationStationId = $externalStation?->id;
                                $logAction .= ' (External: ' . $data['supplier_id'] . ')';
                            }

                            $record->update($updateData);

                            // Log Custody Change
                            if ($destinationStationId) {
                                \Modules\Metrology\Models\AccessLog::create([
                                    'instrument_id' => $record->id,
                                    'user_id' => auth()->id(),
                                    'station_id' => $destinationStationId,
                                    'action' => $logAction,
                                ]);
                            }

                            Notification::make()->success()->title('Enviado para Calibração')->send();
                        }),

                    // ---------------------------------------------------------------
                    // 3. ENVIAR PARA MANUTENÇÃO
                    // ---------------------------------------------------------------
                    Action::make('send_maintenance')
                        ->label('Enviar p/ Manutenção')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Instrument $record) => in_array($record->status, [\Modules\Metrology\Enums\ItemStatus::Active, \Modules\Metrology\Enums\ItemStatus::Rejected, \Modules\Metrology\Enums\ItemStatus::Expired]))
                        ->form([
                            Textarea::make('problem_description')->label('Descrição do Problema')->required(),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            $record->update(['status' => \Modules\Metrology\Enums\ItemStatus::Maintenance]);

                            // Log Maintenance
                            // Ideally maintenance is a "Station" too, checking if exists
                            $maintStation = Station::where('type', 'maintenance')->first();
                            if ($maintStation) {
                                \Modules\Metrology\Models\AccessLog::create([
                                    'instrument_id' => $record->id,
                                    'user_id' => auth()->id(),
                                    'station_id' => $maintStation->id,
                                    'action' => 'maintenance: ' . $data['problem_description'],
                                ]);
                            }

                            Notification::make()->warning()->title('Enviado para Manutenção')->send();
                        }),

                    // Ação: Retorno da Manutenção
                    Action::make('return_maintenance')
                        ->label('Retornou (Calibrar)')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn (Instrument $record) => $record->status === \Modules\Metrology\Enums\ItemStatus::Maintenance)
                        ->form([
                            TextInput::make('repair_notes')->label('O que foi feito?')->required(),
                        ])
                        ->action(function (Instrument $record, array $data) {
                            $record->update(['status' => \Modules\Metrology\Enums\ItemStatus::InCalibration]);
                            // Log
                             \Modules\Metrology\Models\AccessLog::create([
                                'instrument_id' => $record->id,
                                'user_id' => auth()->id(),
                                'station_id' => $record->current_station_id ?? 0, // Keeps same station or default
                                'action' => 'returned_from_maintenance: ' . $data['repair_notes'],
                            ]);
                        }),

                    // Ação: Descarte
                    Action::make('scrap')
                        ->label('Descartar (Sucata)')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (Instrument $record) => in_array($record->status, [\Modules\Metrology\Enums\ItemStatus::Rejected, \Modules\Metrology\Enums\ItemStatus::Maintenance]))
                        ->requiresConfirmation()
                        ->action(fn (Instrument $record) => $record->update(['status' => \Modules\Metrology\Enums\ItemStatus::Scrapped])),
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
