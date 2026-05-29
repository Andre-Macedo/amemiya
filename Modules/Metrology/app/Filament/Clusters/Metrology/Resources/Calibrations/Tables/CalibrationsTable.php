<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Tables;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Metrology\Actions\ExportCalibrationToExcelAction;
use Modules\Metrology\Actions\MergeCertificateAttachmentsAction;
use Modules\Metrology\Actions\PrepareCertificateDataAction;
use Modules\Metrology\Actions\RectifyCalibrationAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\EditCalibration;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\ViewCalibration;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class CalibrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('calibratedItem.name')
                    ->label('Item Calibrado')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHasMorph('calibratedItem', [Instrument::class, ReferenceStandard::class], function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('calibrated_item_type')
                    ->label('Tipo de Item')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)) // Mostra apenas o nome da classe (ex: Instrument)
                    ->badge()
                    ->color(fn (string $state): string => $state === Instrument::class ? 'info' : 'warning'),

                TextColumn::make('replaces.certificate_code')
                    ->label('Retifica')
                    ->badge()
                    ->color('danger')
                    ->url(fn (Calibration $record) => $record->isRectification() ? ViewCalibration::getUrl(['record' => $record->replaces_calibration_id]) : null),

                TextColumn::make('calibration_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('type')->label('Tipo Cal.')
                    ->formatStateUsing(fn (string $state): string => $state === 'internal' ? 'Interna' : 'Externa')
                    ->badge(),
                TextColumn::make('result')->label('Resultado')
                    ->badge(),
                TextColumn::make('status')->label('Status')->badge()
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'submitted',
                        'success' => 'published',
                    ]),
                TextColumn::make('performedBy.name')->label('Executado Por')->searchable()->sortable(),
                TextColumn::make('approvedBy.name')->label('Aprovado Por')->toggleable(isToggledHiddenByDefault: true),            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('type')->label('Tipo Calibração')
                    ->options(['internal' => 'Interna', 'external_rbc' => 'Externa']),
                SelectFilter::make('result')->label('Resultado')
                    ->options(CalibrationResult::class),
                SelectFilter::make('calibrated_item_type')->label('Tipo de Item')
                    ->options([
                        Instrument::class => 'Instrumento',
                        ReferenceStandard::class => 'Padrão de Referência',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Rascunho',
                        'submitted' => 'Aguardando Aprovação',
                        'published' => 'Publicado',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->visible(fn (Calibration $record) => $record->status !== 'published'),
                    DeleteAction::make()->visible(fn (Calibration $record) => $record->status !== 'published'),
                    Action::make('approve')
                        ->label('Aprovar')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form(fn (Calibration $record) => $record->isRectification() ? [
                            Textarea::make('amendment_reason')
                                ->label('Motivo da Retificação')
                                ->required()
                                ->helperText('Explique detalhadamente por que este certificado está substituindo o anterior.'),
                        ] : [])
                        ->visible(fn (Calibration $record) => $record->status !== 'published')
                        ->action(function (Calibration $record, array $data) {
                            $record->update([
                                'status' => 'published',
                                'approved_by_id' => auth()->id(),
                                'approved_at' => now(),
                                'amendment_reason' => $data['amendment_reason'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Calibração Aprovada')
                                ->success()
                                ->send();
                        }),

                    Action::make('rectify')
                        ->label('Retificar')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Retificar Calibração Publicada')
                        ->modalDescription('Deseja criar uma nova versão desta calibração para correção? O registro original será mantido como histórico. Um novo rascunho será gerado.')
                        ->visible(fn (Calibration $record) => $record->status === 'published' && ! $record->replacedBy()->exists())
                        ->action(function (Calibration $record) {
                            $newCalibration = (new RectifyCalibrationAction)->execute($record);

                            Notification::make()
                                ->title('Rascunho de Retificação Criado')
                                ->success()
                                ->send();

                            // Redireciona para editar o novo rascunho
                            return redirect()->to(EditCalibration::getUrl(['record' => $newCalibration->id]));
                        }),
                    Action::make('pdf')
                        ->label('Certificado')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Calibration $record) {
                            $instrument = $record->calibratedItem;

                            $data = (new PrepareCertificateDataAction)->execute($record);

                            $pdf = Pdf::loadView('metrology::pdf.certificate', [
                                'calibration' => $record,
                                'instrument' => $instrument,
                                'results' => $data['results'],
                                'standards' => $data['standards'],
                            ]);

                            return response()->streamDownload(
                                fn () => print ($pdf->output()),
                                "certificado-{$record->certificate_code}.pdf"
                            );
                        })
                        ->visible(fn (Calibration $record) => $record->status === 'published'),

                    Action::make('download_merged')
                        ->label('Certificado com Anexos')
                        ->icon('heroicon-o-paper-clip')
                        ->action(function (Calibration $record) {
                            $pdfContent = (new MergeCertificateAttachmentsAction)->execute($record);

                            return response()->streamDownload(
                                fn () => print ($pdfContent),
                                "dossie-{$record->certificate_code}.pdf"
                            );
                        })
                        ->visible(fn (Calibration $record) => $record->status === 'published'),

                    Action::make('export_excel')
                        ->label('Exportar Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(fn (Calibration $record) => (new ExportCalibrationToExcelAction($record))->download("Calibracao_{$record->certificate_code}.xlsx")),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
