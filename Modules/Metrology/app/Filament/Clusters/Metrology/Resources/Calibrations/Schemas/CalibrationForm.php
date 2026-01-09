<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\MetrologyMath;

class CalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // --- PASSO 1: IDENTIFICAÇÃO ---
                    Wizard\Step::make('Dados Iniciais')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('calibrated_item_type')
                                    ->label('Tipo de Item')
                                    ->options([
                                        Instrument::class => 'Instrumento',
                                        ReferenceStandard::class => 'Padrão de Referência',
                                    ])
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('calibrated_item_id', null);
                                        // Padrões são sempre calibrados fora (regra geral)
                                        if ($state === ReferenceStandard::class) {
                                            $set('type', 'external_rbc');
                                        }
                                    }),

                                Select::make('calibrated_item_id')
                                    ->label('Selecione o Item')
                                    ->options(function (Get $get) {
                                        $type = $get('calibrated_item_type');
                                        if ($type === Instrument::class) {
                                            return Instrument::query()->where('status', "=", \Modules\Metrology\Enums\ItemStatus::Active)->pluck('name', 'id');
                                        }
                                        if ($type === ReferenceStandard::class) {
                                            return ReferenceStandard::query()->pluck('name', 'id');
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() // Live para puxar o fornecedor atual
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // Se for instrumento e estiver num fornecedor, já sugere ele
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
                                    ->label('Tipo de Calibração')
                                    ->options([
                                        'internal' => 'Interna (Laboratório Próprio)',
                                        'external_rbc' => 'Externa (Certificado Fornecedor)',
                                    ])
                                    ->live()
                                    ->required()
                                    ->disabled(fn(Get $get) => $get('calibrated_item_type') === ReferenceStandard::class),

                                Select::make('provider_id')
                                    ->label('Laboratório Fornecedor')
                                    ->options(Supplier::where('is_calibration_provider', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(Get $get) => $get('type') === 'external_rbc')
                                    ->visible(fn(Get $get) => $get('type') === 'external_rbc'),
                            ]),

                            Grid::make(2)->schema([
                                DatePicker::make('calibration_date')
                                    ->label('Data Realizada')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()),

                                Select::make('performed_by_id')
                                    ->label('Registrado Por')
                                    ->relationship('performedBy', 'name')
                                    ->default(auth()->id())
                                    ->searchable()
                                    ->required(),
                            ]),
                        ]),

                    // --- PASSO 2: CHECKLIST (Só Interna) ---
                    Wizard\Step::make('Checklist Técnico')
                        ->description('Execução do procedimento interno.')
                        ->schema([

                            Select::make('checklist_template_id')
                                ->label('Carregar Procedimento')
                                ->options(function (Get $get): Collection {
                                    $itemId = $get('calibrated_item_id');
                                    if (!$itemId || $get('calibrated_item_type') !== Instrument::class) return collect();

                                    $typeId = Instrument::find($itemId)?->instrument_type_id;
                                    return ChecklistTemplate::where('instrument_type_id', $typeId)->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if (!$state) return;
                                    $template = ChecklistTemplate::with('items')->find($state);
                                    if ($template) {
                                        // Preenche o Repeater com os itens do template
                                        $items = $template->items->map(fn($item) => [
                                            'step' => $item->step,
                                            'question_type' => $item->question_type,
                                            'required_readings' => $item->required_readings,
                                            'reference_standard_type_id' => $item->reference_standard_type_id,
                                            'nominal_value' => $item->nominal_value ?? (float)preg_replace('/[^0-9.]/', '', $item->step),
                                            'order' => $item->order, // Importante para ordenação
                                            'readings' => array_fill(0, $item->required_readings, ['value' => null]),
                                        ])->toArray();
                                        $set('checklist_items', $items);
                                    }
                                }),

                            Select::make('primary_kit_id')
                                ->label('Jogo de Blocos Utilizado (Sugestão Inteligente)')
                                ->helperText('Selecione um kit para preencher automaticamente os padrões nas linhas abaixo.')
                                ->options(\Modules\Metrology\Models\ReferenceStandard::whereNull('parent_id')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if (!$state) return;

                                    $kitChildren = \Modules\Metrology\Models\ReferenceStandard::where('parent_id', $state)->get();

                                    $currentItems = $get('checklist_items') ?? [];

                                    $updatedItems = collect($currentItems)->map(function ($item) use ($kitChildren) {
                                        $nominalRequired = $item['nominal_value'] ?? null;

                                        if (!empty($item['reference_standard_type_id']) && $nominalRequired) {

                                            $matchingChild = $kitChildren->filter(function ($child) use ($nominalRequired) {
                                                return abs($child->nominal_value - $nominalRequired) < 0.001;
                                            })->first();

                                            if ($matchingChild) {
                                                $item['reference_standard_id'] = $matchingChild->id;
                                            }
                                        }
                                        return $item;
                                    })->toArray();

                                    $set('checklist_items', $updatedItems);
                                })
                                ->visible(fn(Get $get) => collect($get('checklist_items'))->contains('question_type', 'numeric')),

                            Repeater::make('checklist_items')
                                ->label('Itens de Verificação')
                                ->key('checklist_repeater')
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('step')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(6),

                                        Grid::make(1)->schema([
                                            ToggleButtons::make('result')
                                                ->label('Resultado')
                                                ->options(\Modules\Metrology\Enums\CalibrationResult::class)
                                                ->inline()
                                                ->required()
                                                ->visible(fn(Get $get) => $get('question_type') === 'boolean'),

                                            Select::make('reference_standard_id')
                                                ->label('Padrão Usado')
                                                ->options(function (Get $get) {
                                                    $query = ReferenceStandard::query()
                                                        ->where('reference_standard_type_id', $get('reference_standard_type_id'));
                                                    
                                                    // Filter by nominal value if present (Smart Filtering)
                                                    $nominal = (float) $get('nominal_value');
                                                    if ($nominal > 0) {
                                                        // Use range to avoid float precision issues
                                                        $query->whereBetween('nominal_value', [$nominal - 0.0001, $nominal + 0.0001]);
                                                    }

                                                    return $query->pluck('name', 'id');
                                                })
                                                ->required()
                                                ->visible(fn(Get $get) => $get('question_type') === 'numeric'),

                                            Repeater::make('readings')
                                                ->label('Leituras')
                                                ->schema([
                                                    TextInput::make('value')->numeric()->required()
                                                ])
                                                ->reorderable(false)
                                                ->addable(false)->deletable(false)
                                                ->visible(fn(Get $get) => $get('question_type') === 'numeric'),
                                        ])->columnSpan(6),

                                        Textarea::make('notes')->columnSpanFull()
                                            ->visible(fn(Get $get) => $get('question_type') === 'text'),

                                        Hidden::make('nominal_value'),
                                        Hidden::make('question_type'),
                                        Hidden::make('required_readings'),
                                        Hidden::make('reference_standard_type_id'),
                                        Hidden::make('order'),
                                    ]),
                                ])->visible(fn(Get $get) => $get('checklist_template_id') !== null),
                        ])
                        ->visible(fn(Get $get) => $get('type') === 'internal'
                            && $get('calibrated_item_type') === Instrument::class),

                    Wizard\Step::make('Resultados do Kit')
                        ->schema([
                            Repeater::make('kit_items_results')
                                ->label('Valores Reais das Peças do Kit')
                                ->helperText('Digite o novo valor verdadeiro ou o desvio conforme o certificado.')
                                ->formatStateUsing(function (Get $get) {
                                    $kitId = $get('calibrated_item_id');
                                    if (!$kitId) return [];

                                    $children = \Modules\Metrology\Models\ReferenceStandard::where('parent_id', $kitId)->get();

                                    return $children->map(fn($child) => [
                                        'child_id' => $child->id,
                                        'name' => $child->name,
                                        'nominal_value' => $child->nominal_value,
                                        'new_actual_value' => $child->actual_value, // Valor atual como padrão
                                    ])->toArray();
                                })
                                ->schema([
                                    TextInput::make('name')->label('Peça')->disabled()->dehydrated(),
                                    TextInput::make('nominal_value')->label('Nominal')->disabled()->dehydrated(),
                                    Hidden::make('child_id'),

                                    TextInput::make('new_actual_value')
                                        ->label('Novo Valor Verdadeiro')
                                        ->numeric()
                                        ->required()
                                        ->suffix('mm'),
                                ])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columns(3),
                        ])
                        ->visible(function (Get $get) {
                            $itemId = $get('calibrated_item_id');
                            if (!$itemId) return false;
                            return \Modules\Metrology\Models\ReferenceStandard::where('id', $itemId)->whereHas('children')->exists();
                        }),

                    Wizard\Step::make('Resultados e Certificado')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('temperature')->label('Temperatura')->numeric()->suffix('°C'),
                                TextInput::make('humidity')->label('Umidade')->numeric()->suffix('%'),
                            ]),


                            Section::make('Resultados Consolidados')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('deviation')
                                            ->label('Maior Erro (Tendência)')
                                            ->numeric()
                                            ->readOnly() // O usuário não deve editar se for automático
                                            ->hintAction(
                                                Action::make('calcular')
                                                    ->icon('heroicon-o-calculator')
                                                    ->label('Calcular (Método GUM)')
                                                    ->action(function (Get $get, Set $set) {
                                                        $items = $get('checklist_items') ?? [];

                                                        // Dados do Instrumento (Calibrado)
                                                        $instrument = \Modules\Metrology\Models\Instrument::find($get('calibrated_item_id'));
                                                        $resolution = (float)($instrument?->resolution ?? 0.01);

                                                        $calculator = new \Modules\Metrology\Services\UncertaintyCalculator();

                                                        $maxBias = 0.0;
                                                        $maxUncertainty = 0.0;
                                                        $finalBudget = [];

                                                        foreach ($items as $item) {
                                                            if (($item['question_type'] ?? '') === 'numeric' && !empty($item['readings'])) {

                                                                // 1. Extrair Leituras
                                                                $readings = collect($item['readings'])
                                                                    ->pluck('value')
                                                                    ->map(fn($v) => (float)$v)
                                                                    ->toArray();

                                                                // 2. Dados do Padrão Utilizado neste ponto
                                                                $std = \Modules\Metrology\Models\ReferenceStandard::find($item['reference_standard_id'] ?? null);

                                                                // Se não tem padrão, usa nominal (Cuidado: isso gera erro metodológico, mas evita crash)
                                                                $stdValue = (float)($std?->actual_value ?? ($item['nominal_value'] ?? 0));

                                                                // Incerteza do Padrão (Vem do certificado dele, expandida)
                                                                $stdU = (float)($std?->uncertainty ?? 0);
                                                                // K do padrão (Assumimos 2 se não tivermos o campo cadastrado no padrão)
                                                                $stdK = 2.00;

                                                                // --- CÁLCULO VIA SERVIÇO ---
                                                                $result = $calculator->calculate(
                                                                    $readings,
                                                                    $resolution,
                                                                    $stdValue,
                                                                    $stdU,
                                                                    $stdK
                                                                );

                                                                // D. "Pior Caso"
                                                                if (abs($result['bias']) > abs($maxBias)) {
                                                                    $maxBias = $result['bias'];
                                                                    $finalBudget = $result['budget']; // Guarda o budget do pior caso
                                                                }
                                                                if ($result['expanded_uncertainty'] > $maxUncertainty) {
                                                                    $maxUncertainty = $result['expanded_uncertainty'];
                                                                    if (empty($finalBudget)) $finalBudget = $result['budget'];
                                                                }
                                                            }
                                                        }

                                                        $set('deviation', $maxBias);
                                                        $set('uncertainty', $maxUncertainty);
                                                        $set('uncertainty_budget_data', $finalBudget);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Cálculo ISO GUM Concluído')
                                                            ->body("Erro: {$maxBias} | Incerteza: {$maxUncertainty}")
                                                            ->success()
                                                            ->send();
                                                    }),
                                            ),

                                        TextInput::make('uncertainty')
                                            ->label('Incerteza Expandida (U)')
                                            ->numeric()
                                            ->readOnly(),

                                        TextInput::make('k_factor')
                                            ->default(2.00)
                                            ->readOnly(),
                                    ]),

                                    // Campo de Aprovação Automática (Visual)
                                    Placeholder::make('avaliacao')
                                        ->label('Status Sugerido')
                                        ->content(function (Get $get) {
                                            $erro = (float)$get('deviation');
                                            $incerteza = (float)$get('uncertainty');
                                            // Busca o MPE do instrumento que está sendo calibrado
                                            $instrumento = \Modules\Metrology\Models\Instrument::find($get('calibrated_item_id'));
                                            $mpe = (float)$instrumento?->mpe;

                                            if ($mpe <= 0) return 'Defina o EMP no instrumento.';

                                            if (($erro + $incerteza) <= $mpe) {
                                                return new \Illuminate\Support\HtmlString('<span style="color:green; font-weight:bold">APROVADO (Zona Segura)</span>');
                                            } elseif ($erro <= $mpe) {
                                                return new \Illuminate\Support\HtmlString('<span style="color:orange; font-weight:bold">ZONA DE DÚVIDA (Aprovar c/ Restrição?)</span>');
                                            } else {
                                                return new \Illuminate\Support\HtmlString('<span style="color:red; font-weight:bold">REPROVADO</span>');
                                            }
                                        }),
                                ]),

                            Placeholder::make('calculation_breakdown')
                                ->label('')
                                ->content(function (Get $get) {
                                    $budget = $get('uncertainty_budget_data');
                                    $k = $get('k_factor') ?? 2;
                                    $U = $get('uncertainty');

                                    if (empty($budget) || !is_array($budget)) {
                                        return new \Illuminate\Support\HtmlString('<div class="text-gray-500 italic p-4">Realize o cálculo acima para ver o memorial.</div>');
                                    }

                                    // Prepara totais para passar para a view
                                    $sumSquares = 0;
                                    foreach ($budget as $item) {
                                        $sumSquares += pow($item['standard_uncertainty'], 2);
                                    }
                                    $uc = sqrt($sumSquares);

                                    // Retorna a View (Certifique-se do caminho correto do namespace da view)
                                    // Se estiver num módulo, geralmente é 'metrology::components.calculation-breakdown'
                                    // Se salvou em resources/views normal, é 'components.calculation-breakdown'
                                    return view('metrology::components.calculation-breakdown', [
                                        'budget' => $budget,
                                        'k' => $k,
                                        'ucFormatted' => number_format($uc, 5),
                                        'expandedUFormatted' => number_format($U, 4),
                                    ]);
                                }),

                            Select::make('result')
                                ->label('Decisão Final')
                                ->options(\Modules\Metrology\Enums\CalibrationResult::class)
                                ->required()
                                ->default('approved'),

                            Textarea::make('notes')
                                ->label('Observações Gerais'),

                            FileUpload::make('certificate_path')
                                ->label('Arquivo do Certificado (PDF)')
                                ->directory('certificates/' . date('Y'))
                                ->acceptedFileTypes(['application/pdf'])
                                ->required(fn(Get $get) => $get('type') === 'external_rbc')
                                ->openable()
                                ->downloadable(),
                        ]),
                ])->columnSpanFull(),

                Hidden::make('uncertainty_budget_data'),
            ]);
    }
}
