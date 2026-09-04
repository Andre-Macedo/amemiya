<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Modules\Metrology\DTOs\MeasurementCalculationData;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\UncertaintyCalculator;
use Modules\System\Models\Supplier;

/**
 * Advanced form schema for Calibration records in Filament.
 * Handles polymorphic relations, real-time uncertainty math, and ISO compliance.
 */
class CalibrationForm
{
    /**
     * Configures the multi-step calibration wizard.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // --- STEP 1: INITIAL DATA ---
                    Wizard\Step::make('Dados Iniciais')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('calibrated_item_type')
                                    ->label('Tipo de Ativo')
                                    ->options([
                                        Instrument::class => 'Instrumento de Medição',
                                        ReferenceStandard::class => 'Padrão de Referência',
                                    ])
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('calibrated_item_id', null);
                                        if ($state === ReferenceStandard::class) {
                                            $set('type', 'external_rbc');
                                        }
                                    }),

                                Select::make('calibrated_item_id')
                                    ->label('Identificação do Item')
                                    ->options(function (Get $get) {
                                        $type = $get('calibrated_item_type');
                                        if ($type === Instrument::class) {
                                            return Instrument::query()
                                                ->orderBy('name')
                                                ->get()
                                                ->mapWithKeys(fn ($i) => [$i->id => "{$i->stock_number} - {$i->name}"]);
                                        }
                                        if ($type === ReferenceStandard::class) {
                                            return ReferenceStandard::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        }

                                        return [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('calibrated_item_type') === Instrument::class && $state) {
                                            $instrument = Instrument::find($state);
                                            if ($instrument?->current_supplier_id) {
                                                $set('type', 'external_rbc');
                                                $set('provider_id', $instrument->current_supplier_id);
                                            }
                                        }
                                    }),
                            ]),

                            Grid::make(2)->schema([
                                Select::make('type')
                                    ->label('Modalidade de Calibração')
                                    ->options([
                                        'internal' => 'Interna (Laboratório Próprio)',
                                        'external_rbc' => 'Externa (Certificado Acreditado)',
                                        'external_traceable' => 'Externa (Rastreável)',
                                    ])
                                    ->live()
                                    ->required()
                                    ->disabled(fn (Get $get) => $get('calibrated_item_type') === ReferenceStandard::class),

                                Select::make('provider_id')
                                    ->label('Laboratório Executor')
                                    ->options(Supplier::where('is_calibration_provider', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (Get $get) => in_array($get('type'), ['external_rbc', 'external_traceable']))
                                    ->visible(fn (Get $get) => in_array($get('type'), ['external_rbc', 'external_traceable'])),
                            ]),

                            Grid::make(2)->schema([
                                DatePicker::make('calibration_date')
                                    ->label('Data da Calibração')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()),

                                Select::make('performed_by_id')
                                    ->label('Técnico Responsável')
                                    ->relationship('performedBy', 'name')
                                    ->default(auth()->id())
                                    ->searchable()
                                    ->required(),
                            ]),
                        ]),

                    // --- STEP 2: CHECKLIST (INTERNAL ONLY) ---
                    Wizard\Step::make('Execução Técnica')
                        ->description('Medições e conformidade.')
                        ->schema([
                            Select::make('checklist_template_id')
                                ->label('Procedimento Aplicável')
                                ->options(function (Get $get): Collection {
                                    $itemId = $get('calibrated_item_id');
                                    if (! $itemId || $get('calibrated_item_type') !== Instrument::class) {
                                        return collect();
                                    }

                                    $typeId = Instrument::find($itemId)?->instrument_type_id;

                                    return ChecklistTemplate::where('instrument_type_id', $typeId)->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if (! $state) {
                                        return;
                                    }
                                    $template = ChecklistTemplate::with('items')->find($state);
                                    if ($template) {
                                        $items = $template->items->map(fn ($item) => [
                                            'step' => $item->step,
                                            'question_type' => $item->question_type,
                                            'required_readings' => $item->required_readings,
                                            'reference_standard_type_id' => $item->reference_standard_type_id,
                                            'nominal_value' => $item->nominal_value,
                                            'order' => $item->order,
                                            'readings' => array_fill(0, $item->required_readings, ['value' => null]),
                                        ])->toArray();
                                        $set('checklist_items', $items);
                                    }
                                }),

                            Section::make('Suporte Metrológico')
                                ->compact()
                                ->schema([
                                    Select::make('primary_kit_id')
                                        ->label('Kit de Padrões Utilizado')
                                        ->placeholder('Selecione para auto-preenchimento')
                                        ->options(ReferenceStandard::whereNull('parent_id')->pluck('name', 'id'))
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (! $state) {
                                                return;
                                            }
                                            $kitChildren = ReferenceStandard::where('parent_id', $state)->get();
                                            $currentItems = $get('checklist_items') ?? [];

                                            $updatedItems = collect($currentItems)->map(function ($item) use ($kitChildren) {
                                                $nominal = $item['nominal_value'] ?? null;
                                                if (! empty($item['reference_standard_type_id']) && $nominal) {
                                                    $match = $kitChildren->filter(fn ($c) => abs($c->nominal_value - $nominal) < 0.0001)->first();
                                                    if ($match) {
                                                        $item['reference_standard_id'] = $match->id;
                                                    }
                                                }

                                                return $item;
                                            })->toArray();
                                            $set('checklist_items', $updatedItems);
                                        }),
                                ])->visible(fn (Get $get) => ! empty($get('checklist_items'))),

                            Repeater::make('checklist_items')
                                ->label('Pontos de Medição')
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('step')
                                            ->label('Ponto/Requisito')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(4),

                                        Grid::make(1)->schema([
                                            ToggleButtons::make('result')
                                                ->label('Conformidade')
                                                ->options([
                                                    'pass' => 'Atende',
                                                    'fail' => 'Não Atende',
                                                ])
                                                ->colors(['pass' => 'success', 'fail' => 'danger'])
                                                ->inline()
                                                ->visible(fn (Get $get) => $get('question_type') === 'boolean'),

                                            Select::make('reference_standard_id')
                                                ->label('Padrão')
                                                ->options(function (Get $get, ?string $state) {
                                                    $typeId = $get('reference_standard_type_id');
                                                    if (! $typeId) {
                                                        return [];
                                                    }

                                                    return ReferenceStandard::query()
                                                        ->where('reference_standard_type_id', $typeId)
                                                        ->where(function ($q) use ($state) {
                                                            $q->where(function ($sub) {
                                                                $sub->where('status', ItemStatus::Active)
                                                                    ->where('calibration_due', '>=', now()->startOfDay());
                                                            });
                                                            if ($state) {
                                                                $q->orWhere('id', $state);
                                                            }
                                                        })
                                                        ->pluck('name', 'id');
                                                })
                                                ->required()
                                                ->columnSpanFull()
                                                ->visible(fn (Get $get) => $get('question_type') === 'numeric'),

                                            Repeater::make('readings')
                                                ->label('Leituras Brutas')
                                                ->schema([
                                                    TextInput::make('value')
                                                        ->numeric()
                                                        ->required()
                                                        ->extraInputAttributes(['class' => 'text-right']),
                                                ])
                                                ->grid(3)
                                                ->addable(false)->deletable(false)
                                                ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                        ])->columnSpan(8),
                                    ]),
                                ])->visible(fn (Get $get) => $get('checklist_template_id') !== null),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'internal' && $get('calibrated_item_type') === Instrument::class),

                    // --- STEP 3: RESULTS & ISO CALCULATIONS ---
                    Wizard\Step::make('Memorial de Cálculo')
                        ->schema([
                            Section::make('Condições Ambientais')
                                ->description('Requisito ISO 17025.')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('temperature')->label('Temperatura (°C)')->numeric()->required(fn (Get $get) => $get('type') === 'internal'),
                                        TextInput::make('humidity')->label('Umidade (%)')->numeric()->required(fn (Get $get) => $get('type') === 'internal'),
                                    ]),
                                ]),

                            Section::make('Resultados Consolidados (GUM)')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('deviation')
                                            ->label('Erro Máximo (E)')
                                            ->numeric()
                                            ->readOnly()
                                            ->hintAction(
                                                Action::make('calc_gum')
                                                    ->label('Calcular ISO GUM')
                                                    ->icon('heroicon-m-calculator')
                                                    ->action(function (Get $get, Set $set) {
                                                        $items = $get('checklist_items') ?? [];
                                                        $inst = Instrument::find($get('calibrated_item_id'));
                                                        $res = (float) ($inst?->resolution ?? 0.001);

                                                        $calc = new UncertaintyCalculator;
                                                        $maxE = 0.0;
                                                        $maxU = 0.0;
                                                        $budget = [];

                                                        foreach ($items as $item) {
                                                            if (($item['question_type'] ?? '') !== 'numeric' || empty($item['readings'])) {
                                                                continue;
                                                            }

                                                            $readings = collect($item['readings'])->pluck('value')->filter()->map(fn ($v) => (float) $v)->toArray();
                                                            if (count($readings) < 1) {
                                                                continue;
                                                            }

                                                            $std = ReferenceStandard::find($item['reference_standard_id'] ?? null);
                                                            $stdV = (float) ($std?->actual_value ?? $item['nominal_value']);

                                                            $resObj = $calc->calculate(new MeasurementCalculationData(
                                                                readings: $readings,
                                                                resolution: $res,
                                                                standardActualValue: $stdV,
                                                                standardUncertainty: (float) ($std?->uncertainty ?? 0),
                                                                standardK: 2.00,
                                                                temperature: (float) $get('temperature'),
                                                                cte: (float) ($inst?->material?->cte ?? 11.5)
                                                            ));

                                                            if (abs($resObj->bias) > abs($maxE)) {
                                                                $maxE = $resObj->bias;
                                                                $budget = $resObj->budget;
                                                            }
                                                            if ($resObj->expandedUncertainty > $maxU) {
                                                                $maxU = $resObj->expandedUncertainty;
                                                            }
                                                        }

                                                        $set('deviation', $maxE);
                                                        $set('uncertainty', $maxU);
                                                        $set('calculation_data', $budget);
                                                    })
                                            ),

                                        TextInput::make('uncertainty')->label('Incerteza (U)')->numeric()->readOnly(),
                                        TextInput::make('k_factor')->label('Fator k')->default(2.00)->readOnly(),
                                    ]),

                                    Placeholder::make('compliance_suggestion')
                                        ->label('Análise de Conformidade (ISO 17025)')
                                        ->content(function (Get $get, Set $set) {
                                            $e = abs((float) $get('deviation'));
                                            $u = (float) $get('uncertainty');
                                            $inst = Instrument::find($get('calibrated_item_id'));
                                            $mpe = $inst?->getMaximumPermissibleError();

                                            if (! $mpe) {
                                                return 'MPE não definido no instrumento.';
                                            }

                                            $rule = $inst->getDecisionRule();
                                            $strategy = $inst->getDecisionRuleStrategy();
                                            $isPass = $strategy->evaluate($e, $u, $mpe);

                                            // Lógica Adicional para sugestão automática do campo 'result'
                                            if (! $isPass) {
                                                $set('result', CalibrationResult::Rejected);

                                                return new HtmlString('<span class="text-danger-600 font-bold">✗ NÃO CONFORME (Fora do Critério)</span><br><small>O desvio excedeu o limite definido pela regra: '.$rule.'</small>');
                                            }

                                            // Se passou, mas se for Guard Band, podemos verificar se estaria na zona de dúvida da aceitação simples
                                            if ($rule === 'guard_band') {
                                                if ($e > ($mpe - $u)) {
                                                    // Isso não deve acontecer se $isPass é true, mas para clareza:
                                                    // Se E <= MPE - U -> Verde Total
                                                }

                                                if ($e > ($mpe - 2 * $u)) { // Exemplo de alerta de proximidade
                                                    // return ...
                                                }
                                            }

                                            $set('result', CalibrationResult::Approved);

                                            return new HtmlString('<span class="text-success-600 font-bold">✓ CONFORME (Dentro do Critério)</span>');
                                        }),
                                ]),

                            Select::make('result')
                                ->label('Decisão Final')
                                ->options(CalibrationResult::class)
                                ->required()
                                ->native(false),

                            FileUpload::make('certificate_path')
                                ->label('Certificado PDF')
                                ->directory('certificates/'.date('Y'))
                                ->acceptedFileTypes(['application/pdf'])
                                ->required(fn (Get $get) => $get('type') !== 'internal')
                                ->openable()->downloadable(),
                        ]),
                ])->columnSpanFull(),

                Hidden::make('calculation_data'),
            ]);
    }
}
