<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Modules\Metrology\Models\Instrument;

class InstrumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Detalhes do Instrumento')->tabs([
                    Tabs\Tab::make('Informações Gerais')
                        ->schema([
                            Grid::make(3)->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')->label('Nome'),
                                        TextEntry::make('instrumentType.name')->label('Tipo'),
                                        TextEntry::make('serial_number')->label('Número de Série'),
                                        TextEntry::make('asset_tag')->label('Nº Patrimônio')->placeholder('N/A'),
                                        TextEntry::make('precision')->label('Precisão')->formatStateUsing(fn($state) => ucfirst($state)),
                                        TextEntry::make('location')->label('Localização')->placeholder('N/A'),
                                        TextEntry::make('acquisition_date')->label('Data de Aquisição')->date('d/m/Y'),
                                        TextEntry::make('calibration_due')->label('Venc. Calibração')->date('d/m/Y')
                                            ->color(function ($record) {
                                                $dueDate = Carbon::parse($record->calibration_due);
                                                $now = Carbon::now();

                                                if ($dueDate->isPast()) {
                                                    return 'danger';
                                                }

                                                if ($dueDate->isBetween($now, $now->copy()->addDays(14))) {
                                                    return 'warning';
                                                }

                                                return 'success';
                                            }),
                                        TextEntry::make('status')->label('Status')
                                            ->formatStateUsing(fn(string $state): string => ucfirst(str_replace('_', ' ', $state)))
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                'active' => 'success',
                                                'in_calibration' => 'warning',
                                                'expired' => 'danger',
                                                default => 'gray',
                                            }),
                                    ])->columnSpan(2),

                                ImageEntry::make('image_path')
                                    ->hiddenLabel()
                                    ->height(250)
                                    ->defaultImageUrl(url('/images/placeholder.png'))
                                    ->columnSpan(1),
                            ]),

                            Section::make('Observações')
                                ->schema([
                                    TextEntry::make('notes')->label('')->html()->placeholder('Nenhuma observação.'),
                                ]),
                        ]),

                    Tabs\Tab::make('Última Calibração')
                        ->schema(function (Instrument $record) {
                            $latestCalibration = $record->calibrations()->with(['performedBy', 'checklist.items.referenceStandard'])->latest()->first();

                            if (!$latestCalibration) {
                                return [
                                    Placeholder::make('no_calibration')->content('Nenhum registro de calibração encontrado para este instrumento.'),
                                ];
                            }

                            $schema = [
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('calibration_date')->label('Data da Calibração')->date('d/m/Y')->getStateUsing(fn() => $latestCalibration->calibration_date),
                                        TextEntry::make('result')
                                            ->label('Resultado')
                                            ->badge()
                                            ->getStateUsing(fn() => $latestCalibration->result)
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'approved' => 'Aprovado',
                                                'rejected' => 'Reprovado',
                                                default => ucfirst($state ?? 'N/A'),
                                            })
                                            ->color(fn (?string $state): string => match ($state) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('performedBy.name')->label('Executado Por')->getStateUsing(fn() => $latestCalibration->performedBy->name ?? 'N/A'),
                                        TextEntry::make('notes')->label('Observações')->getStateUsing(fn() => $latestCalibration->notes)->placeholder('N/A')->columnSpanFull(),
                                    ])->columnSpanFull(), // `contained(false)` ajuda a remover espaçamento extra
                            ];

                            if ($latestCalibration->checklist && $latestCalibration->checklist->items->isNotEmpty()) {
                                $checklistName = $latestCalibration->checklist->checklistTemplate?->name ?? 'Checklist Executado';
                                $schema[] = Section::make($checklistName)
                                    ->schema([
                                        RepeatableEntry::make('checklist.items')
                                            ->label('')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextEntry::make('step')->label('Passo')->columnSpan(2),
                                                    TextEntry::make('result')
                                                        ->label('Resultado')
                                                        ->badge()
                                                        ->formatStateUsing(fn(?string $state): string => match ($state) {
                                                            'approved' => 'Aprovado',
                                                            'rejected' => 'Reprovado',
                                                            default => ucfirst($state ?? 'N/A'),
                                                        })
                                                        ->color(fn(?string $state): string => match ($state) {
                                                            'approved' => 'success',
                                                            'rejected' => 'danger',
                                                            default => 'gray',
                                                        }),
                                                ]),
                                                TextEntry::make('readings')->label('Leituras')
                                                    ->formatStateUsing(function ($state): string {
                                                        if (is_array($state)) {
                                                            return empty($state) ? 'N/A' : implode(' | ', $state);
                                                        }
                                                        return (string)$state;
                                                    })->visible(fn($record) => $record->question_type === 'numeric'),
                                                TextEntry::make('referenceStandard.name')->label('Padrão Utilizado')->placeholder('N/A')->visible(fn($record) => $record->question_type === 'numeric'),
                                                TextEntry::make('notes')->label('Anotações')->placeholder('N/A')->visible(fn($record) => $record->question_type === 'text' && !empty($record->notes)),
                                            ])
                                            ->getStateUsing(fn() => $latestCalibration->checklist->items),
                                    ]);
                            }

                            return $schema;
                        }),
                ])->columnSpanFull(),
            ]);
    }
}
