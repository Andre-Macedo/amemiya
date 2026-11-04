<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Simulation Configuration Card -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 border-b border-gray-100 pb-3 dark:border-gray-800">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-5 w-5 text-primary-500" />
                    Parâmetros da Janela de Exposição & Deriva Simulatória
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Avalie o impacto retroativo de um desvio detectado no padrão de referência conforme ABNT NBR ISO/IEC 17025:2017 §7.10.
                </p>
            </div>

            <form wire:submit.prevent="runSimulation" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Reference Standard -->
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Padrão de Referência Comprometido *
                        </label>
                        <select wire:model.live="standard_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Selecione o padrão de referência...</option>
                            @foreach($this->standards as $standard)
                                <option value="{{ $standard->id }}">
                                    {{ $standard->stock_number }} - {{ $standard->name }} (Nominal: {{ $standard->nominal_value }} {{ $standard->unit }} | Validade: {{ $standard->calibration_due?->format('d/m/Y') ?? 'N/D' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Drift Direction -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Direção da Deriva
                        </label>
                        <select wire:model="drift_direction" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="worst_case">Pior Caso (± Afastando do Zero)</option>
                            <option value="positive">Positiva (+ Desvio Aditivo)</option>
                            <option value="negative">Negativa (- Desvio Subtrativo)</option>
                        </select>
                    </div>

                    <!-- Drift Magnitude -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Magnitude da Deriva (|Δ|) *
                        </label>
                        <input type="number" step="0.0001" min="0" wire:model="drift_magnitude" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="Ex: 0.02" required>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Início da Janela de Exposição
                        </label>
                        <input type="date" wire:model="start_date" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Término da Janela de Exposição
                        </label>
                        <input type="date" wire:model="end_date" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <!-- Additional Uncertainty -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Incerteza Adicional no Padrão (u_add)
                        </label>
                        <input type="number" step="0.0001" min="0" wire:model="additional_uncertainty" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="0.0000">
                    </div>

                    <!-- Justification / Reason -->
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Motivo / Observação da Simulação
                        </label>
                        <input type="text" wire:model="reason" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <x-filament::button type="submit" color="primary" icon="heroicon-m-play">
                        <span wire:loading.remove wire:target="runSimulation">Executar Simulação de Impacto</span>
                        <span wire:loading wire:target="runSimulation">Simulando...</span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        @if($hasExecuted && $simulation)
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Calibrations -->
                <div class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Calibrações na Janela</div>
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ $simulation['summary']['total_calibrations'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invalidated (Recall) -->
                <div class="fi-section rounded-xl p-5 shadow-sm ring-1 {{ $simulation['summary']['invalidated_count'] > 0 ? 'bg-red-50 ring-red-200 dark:bg-red-950/20 dark:ring-red-900/30' : 'bg-white ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' }}">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg {{ $simulation['summary']['invalidated_count'] > 0 ? 'bg-red-200 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }} p-2">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-medium {{ $simulation['summary']['invalidated_count'] > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                Invalidados (Recall Crítico)
                            </div>
                            <div class="text-2xl font-bold tracking-tight {{ $simulation['summary']['invalidated_count'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">
                                {{ $simulation['summary']['invalidated_count'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unique Instruments -->
                <div class="fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Instrumentos Afetados</div>
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ $simulation['summary']['unique_instruments'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Severity Level -->
                <div class="fi-section rounded-xl p-5 shadow-sm ring-1 {{ $simulation['summary']['severity_level'] === 'CRITICAL' ? 'bg-red-50 ring-red-200 dark:bg-red-950/20 dark:ring-red-900/30' : ($simulation['summary']['severity_level'] === 'WARNING' ? 'bg-amber-50 ring-amber-200 dark:bg-amber-950/20 dark:ring-amber-900/30' : 'bg-green-50 ring-green-200 dark:bg-green-950/20 dark:ring-green-900/30') }}">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg p-2 {{ $simulation['summary']['severity_level'] === 'CRITICAL' ? 'bg-red-200 text-red-700' : ($simulation['summary']['severity_level'] === 'WARNING' ? 'bg-amber-200 text-amber-700' : 'bg-green-200 text-green-700') }}">
                            <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400">Nível de Severidade</div>
                            <div class="text-lg font-bold tracking-tight uppercase {{ $simulation['summary']['severity_level'] === 'CRITICAL' ? 'text-red-700 dark:text-red-400' : ($simulation['summary']['severity_level'] === 'WARNING' ? 'text-amber-700 dark:text-amber-400' : 'text-green-700 dark:text-green-400') }}">
                                {{ $simulation['summary']['severity_level'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendation & Actions Box -->
            <div class="rounded-xl border p-5 {{ $simulation['summary']['severity_level'] === 'CRITICAL' ? 'border-red-200 bg-red-50/50 dark:border-red-900/50 dark:bg-red-950/10' : 'border-blue-200 bg-blue-50/50 dark:border-blue-900/50 dark:bg-blue-950/10' }}">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h4 class="text-sm font-semibold {{ $simulation['summary']['severity_level'] === 'CRITICAL' ? 'text-red-900 dark:text-red-300' : 'text-blue-900 dark:text-blue-300' }} flex items-center gap-2">
                            <x-filament::icon icon="heroicon-m-information-circle" class="h-5 w-5" />
                            Parecer Técnico & Recomendação de Ação Corretiva
                        </h4>
                        <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed max-w-3xl">
                            {{ $simulation['summary']['recommendation'] }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <x-filament::button wire:click="downloadPdf" color="danger" icon="heroicon-m-document-arrow-down" size="sm">
                            Baixar Laudo Técnico de Recall (PDF)
                        </x-filament::button>
                        <a href="{{ route('filament.admin.metrology.resources.non-conformities.create') }}" target="_blank" class="fi-btn fi-btn-size-sm fi-btn-color-warning inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600">
                            <x-filament::icon icon="heroicon-m-plus-circle" class="h-4 w-4" />
                            Abrir RNC
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detailed Calibrations Table -->
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-gray-500" />
                        Detalhamento das Calibrações na Janela de Exposição
                    </h4>

                    <!-- Filter Pills -->
                    <div class="flex items-center gap-1.5 text-xs">
                        <button type="button" wire:click="setFilter('all')" class="px-2.5 py-1 rounded-full font-medium {{ $activeFilter === 'all' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            Todos ({{ count($simulation['items']) }})
                        </button>
                        <button type="button" wire:click="setFilter('invalidated')" class="px-2.5 py-1 rounded-full font-medium {{ $activeFilter === 'invalidated' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            Invalidados ({{ $simulation['summary']['invalidated_count'] }})
                        </button>
                        <button type="button" wire:click="setFilter('critical')" class="px-2.5 py-1 rounded-full font-medium {{ $activeFilter === 'critical' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            Alto Risco ({{ $simulation['summary']['critical_count'] }})
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Certificado / Data</th>
                                <th class="px-4 py-3">Instrumento / Tag</th>
                                <th class="px-4 py-3">Localização</th>
                                <th class="px-4 py-3">MPE</th>
                                <th class="px-4 py-3">Erro (Orig -> Simulado)</th>
                                <th class="px-4 py-3">Incerteza (U)</th>
                                <th class="px-4 py-3">TUR</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3">Ação Recomendada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($this->filteredItems as $row)
                                <tr class="{{ $row['is_invalidated'] ? 'bg-red-50/70 dark:bg-red-950/20' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-gray-900 dark:text-white">{{ $row['cert_code'] }}</div>
                                        <div class="text-[10px] text-gray-500">{{ $row['date'] }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $row['instrument_name'] }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">Tag: {{ $row['tag'] }} | SN: {{ $row['serial'] }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $row['location'] }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $row['mpe'] ? '±' . $row['mpe'] : '-' }}</td>
                                    <td class="px-4 py-3 font-mono">
                                        <span class="text-gray-500">{{ $row['original_deviation'] }}</span>
                                        <span class="text-gray-400">-></span>
                                        <span class="font-bold {{ $row['is_invalidated'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $row['simulated_deviation'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono">±{{ $row['simulated_uncertainty'] }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $row['tur'] ? number_format($row['tur'], 1) : '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row['is_invalidated'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-900/50 dark:text-red-300 animate-pulse">
                                                INVALIDADO
                                            </span>
                                        @elseif($row['simulated_pass'])
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                CONFORME
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                REPROVADO
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-[11px] font-semibold {{ $row['risk'] === 'CRITICAL' ? 'text-red-600 dark:text-red-400' : ($row['risk'] === 'MODERATE' ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400') }}">
                                            {{ $row['action'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        Nenhuma calibração encontrada para os critérios selecionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
