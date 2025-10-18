<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class CalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Detalhes da Calibração')
                        ->schema([
                            Select::make('instrument_id')
                                ->label('Instrumento')
                                ->relationship('instrument', 'name')
                                ->live()
                                ->searchable()
                                ->required(),
                            DatePicker::make('calibration_date')
                                ->label('Data')
                                ->required()
                                ->default(now()),
                            Select::make('type')
                                ->label('Tipo')
                                ->options([
                                    'internal' => 'Interna',
                                    'external_rbc' => 'Externa RBC',
                                ])
                                ->default('internal')
                                ->live()
                                ->required(),
                            Select::make('performed_by_id')
                                ->label('Usuario')
                                ->relationship('performedBy', 'name')
                                ->searchable()
                                ->required(),
                        ])->columns(2),

                    Wizard\Step::make('Seleção do Checklist')
                        ->description('Escolha o procedimento de calibração a ser seguido.')
                        ->schema([
                            Select::make('checklist_template_id')
                                ->label('Procedimento / Checklist')
                                ->options(function (Get $get): Collection {
                                    $instrumentId = $get('instrument_id');
                                    if (!$instrumentId) {
                                        return collect();
                                    }
                                    $instrumentTypeId = Instrument::find($instrumentId)?->instrument_type_id;
                                    return ChecklistTemplate::where('instrument_type_id', $instrumentTypeId)->pluck('name', 'id');
                                })
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if (!$state) {
                                        return;
                                    }
                                    $template = ChecklistTemplate::with('items')->find($state);
                                    if ($template) {
                                        $items = $template->items->map(fn ($item) => [
                                            'step' => $item->step,
                                            'question_type' => $item->question_type,
                                            'required_readings' => $item->required_readings,
                                            'reference_standard_type_id' => $item->reference_standard_type_id,
                                        ])->toArray();
                                        $set('checklist_items', $items);
                                    }
                                }),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'internal'),

                    Wizard\Step::make('Execução do Checklist')
                        ->schema([
                            Repeater::make('checklist_items')
                                ->label('Procedimento')
                                ->schema([
                                    TextInput::make('step')
                                        ->label('Passo')
                                        ->disabled(),
                                    Select::make('question_type')
                                        ->disabled()
                                        ->options(['boolean' => 'Sim/Não', 'numeric' => 'Numérico', 'text' => 'Texto'])
                                        ->label('Tipo')
                                        ->hidden(),
                                    Toggle::make('completed')
                                        ->label('Completo')
                                        ->visible(fn (Get $get) => $get('question_type') === 'boolean'),
                                    Repeater::make('readings')
                                        ->label('Leituras')
                                        ->schema([
                                            TextInput::make('value')
                                                ->label('Valor')
                                                ->numeric()
                                                ->required(),
                                        ])
                                        ->minItems(fn (Get $get) => $get('required_readings') ?: 1)
                                        ->maxItems(fn (Get $get) => $get('required_readings') ?: 1)
                                        ->visible(function (Get $get) {return $get('question_type') === 'numeric';})
                                        ->reorderable(false),
                                    Select::make('reference_standard_id')
                                        ->label('Padrão Utilizado')
                                        ->options(function (Get $get) {
                                            $typeId = $get('reference_standard_type_id');
                                            if (!$typeId) return [];
                                            return ReferenceStandard::where('reference_standard_type_id', $typeId)->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->required()
                                        ->visible(fn (Get $get) => $get('question_type') === 'numeric' && !empty($get('reference_standard_type_id'))),
                                    Textarea::make('notes')
                                        ->label('Observações')
                                        ->visible(fn (Get $get) => $get('question_type') === 'text'),
                                ])
                                ->addable(false)->deletable(false)->reorderable(false)->columnSpanFull(),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'internal' && !empty($get('checklist_template_id'))),

                    Wizard\Step::make('Resultados e Conclusão')
                        ->schema([
                            Select::make('result')
                                ->label('Resultado')
                                ->options(['approved' => 'Aprovado', 'rejected' => 'Rejeitado']),
                            TextInput::make('deviation')
                                ->label('Desvio')
                                ->numeric()->suffix('mm'),
                            TextInput::make('uncertainty')
                                ->label('Incerteza')
                                ->numeric()->suffix('mm'),
                            Select::make('calibration_interval')
                                ->label('Intervalo para Próxima Calibração (meses)')
                                ->options([6 => '6 meses', 12 => '12 meses', 18 => '18 meses', 24 => '24 meses'])
                                ->default(12)->required(),
                            Textarea::make('notes')
                                ->label('Observações')
                                ->columnSpanFull(),
                        ]),
                    Wizard\Step::make('Certificado')
                        ->schema([
                            FileUpload::make('certificate_path')
                                ->label('Certificado')
                                ->helperText('Upload certificado externo')
                                ->directory('calibration-certificates')
                                ->acceptedFileTypes(['application/pdf'])
                                ->required(),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'external_rbc'),
                ])->columnSpanFull(),
            ]);
    }
}
