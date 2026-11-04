<x-filament-panels::page>
    <div class="space-y-6" x-data="custodyScanner()">

        <!-- Fast Scan Bar & Camera Interface -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-qr-code" class="h-6 w-6 text-primary-500" />
                        Estação de Leitura e Custódia Metrológica (F-08)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Utilize leitores de código de barras USB, tags RFID/NFC ou a câmera do seu dispositivo para movimentação rápida.
                    </p>
                </div>

                <!-- Camera Toggle Button -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleCamera()" :class="isCameraActive ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold text-white shadow-sm transition">
                        <x-filament::icon icon="heroicon-m-camera" class="h-4 w-4" />
                        <span x-text="isCameraActive ? 'Fechar Câmera' : 'Leitor por Câmera / Webcam'"></span>
                    </button>
                </div>
            </div>

            <!-- Camera Viewfinder -->
            <div x-show="isCameraActive" x-cloak class="mt-4 flex flex-col items-center justify-center rounded-xl bg-gray-950 p-4 border border-gray-800">
                <div class="relative w-full max-w-md aspect-video overflow-hidden rounded-lg bg-black flex items-center justify-center">
                    <video x-ref="videoElement" class="w-full h-full object-cover" playsinline autoplay muted></video>
                    <!-- Aiming reticle overlay -->
                    <div class="absolute inset-0 border-2 border-primary-500/60 pointer-events-none rounded-lg flex items-center justify-center">
                        <div class="w-48 h-32 border-2 border-dashed border-primary-400 rounded-md animate-pulse flex items-center justify-center">
                            <span class="text-[10px] text-primary-200 bg-black/60 px-2 py-0.5 rounded">Aponte o código de barras ou QR</span>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 mt-2 text-center" x-text="cameraStatus"></p>
            </div>

            <!-- Manual / USB Scanner Input -->
            <div class="mt-4">
                <form wire:submit.prevent="lookupInstrument" class="flex gap-2">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            wire:model="search_code"
                            placeholder="Escaneie ou digite o Nº de Patrimônio, Nº de Série ou Tag NFC (Pressione Enter)..."
                            class="w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            autofocus
                        >
                    </div>
                    <x-filament::button type="submit" color="primary" icon="heroicon-m-arrow-right">
                        Localizar
                    </x-filament::button>
                </form>
            </div>
        </div>

        @if($scannedInstrument)
            <!-- Active Scanned Instrument & Movement Action Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column: Instrument Card -->
                <div class="lg:col-span-5 fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4">
                    <div class="flex items-start justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                                {{ $scannedInstrument['instrument_type'] }}
                            </span>
                            <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $scannedInstrument['name'] }}
                            </h4>
                        </div>
                        <button type="button" wire:click="clearInstrument" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/50">
                            <span class="text-gray-500 text-[10px] uppercase font-semibold">Código Patrimonial</span>
                            <div class="font-mono font-bold text-gray-900 dark:text-white text-sm">
                                {{ $scannedInstrument['stock_number'] ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/50">
                            <span class="text-gray-500 text-[10px] uppercase font-semibold">Nº de Série</span>
                            <div class="font-mono font-bold text-gray-900 dark:text-white text-sm">
                                {{ $scannedInstrument['serial_number'] ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/50">
                            <span class="text-gray-500 text-[10px] uppercase font-semibold">Localização Atual</span>
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ $scannedInstrument['current_station_name'] }}
                            </div>
                        </div>

                        <div class="rounded-lg p-2.5 {{ $scannedInstrument['is_overdue'] ? 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300' : 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' }}">
                            <span class="text-[10px] uppercase font-semibold">Validade Calibração</span>
                            <div class="font-bold font-mono">
                                {{ $scannedInstrument['calibration_due'] }}
                                @if($scannedInstrument['is_overdue'])
                                    <span class="block text-[9px] font-extrabold uppercase text-red-600">Vencida!</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($scannedInstrument['is_critical'])
                        <div class="flex items-center gap-2 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4 shrink-0 text-amber-600" />
                            <span><strong>Instrumento de Referência Crítico:</strong> Requer conferência obrigatória de integridade física.</span>
                        </div>
                    @endif

                    <div class="pt-2 text-right">
                        <a href="{{ route('filament.admin.metrology.resources.instruments.view', $scannedInstrument['id']) }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                            Ver prontuário completo do instrumento
                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>

                <!-- Right Column: Custody Movement Form -->
                <div class="lg:col-span-7 fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-5 w-5 text-primary-500" />
                        Registrar Movimentação de Custódia
                    </h4>

                    <form wire:submit.prevent="confirmMovement" class="space-y-4">
                        <!-- Movement Type Pills -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo de Operação *
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="movement_type" value="checkout" class="peer sr-only">
                                    <div class="rounded-lg border border-gray-200 p-3 text-center text-xs font-semibold peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-gray-700 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                        Check-out (Saída)
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="movement_type" value="checkin" class="peer sr-only">
                                    <div class="rounded-lg border border-gray-200 p-3 text-center text-xs font-semibold peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-gray-700 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                        Check-in (Devolução)
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="movement_type" value="transfer" class="peer sr-only">
                                    <div class="rounded-lg border border-gray-200 p-3 text-center text-xs font-semibold peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-gray-700 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                        Transferência
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="movement_type" value="inventory_check" class="peer sr-only">
                                    <div class="rounded-lg border border-gray-200 p-3 text-center text-xs font-semibold peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-gray-700 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                        Inventário
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Target Station -->
                        <div x-show="['checkout', 'transfer'].includes('{{ $movement_type }}')">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Estação / Posto de Trabalho de Destino *
                            </label>
                            <select wire:model="to_station_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Selecione o destino da movimentação...</option>
                                @foreach($this->stations as $station)
                                    <option value="{{ $station->id }}">{{ $station->name }} ({{ $station->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Notes / Justification -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Observações / Ordem de Serviço / Finalidade (Opcional)
                            </label>
                            <input type="text" wire:model="notes" placeholder="Ex: Utilização na Linha de Usinagem CNC-02 para medição do Lote 4402" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <x-filament::button type="submit" color="primary" icon="heroicon-m-check">
                                Confirmar e Registrar Movimentação
                            </x-filament::button>
                        </div>
                    </form>
                </div>

            </div>
        @endif

        <!-- Recent Movements Live Stream Table -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-gray-500" />
                    Histórico Recente de Movimentações (Cadeia de Custódia)
                </h4>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3">Data / Hora</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Instrumento / Código</th>
                            <th class="px-4 py-3">Origem -> Destino</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($this->recentMovements as $mov)
                            <tr>
                                <td class="px-4 py-3 font-mono text-[11px] text-gray-500">
                                    {{ $mov->created_at?->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $typeBadge = match($mov->type) {
                                            'checkout' => ['bg' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', 'label' => 'Check-out (Saída)'],
                                            'checkin' => ['bg' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300', 'label' => 'Check-in (Devolução)'],
                                            'transfer' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'label' => 'Transferência'],
                                            default => ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300', 'label' => 'Inventário'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $typeBadge['bg'] }}">
                                        {{ $typeBadge['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $mov->instrument?->name ?? 'Instrumento' }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">Tag: {{ $mov->instrument?->stock_number ?? $mov->tag_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-500">{{ $mov->fromStation?->name ?? 'Almoxarifado' }}</span>
                                    <span class="text-gray-400">-></span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $mov->toStation?->name ?? 'Almoxarifado' }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $mov->user?->name ?? 'Sistema' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-[11px]">
                                    {{ $mov->metadata['notes'] ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                    Nenhuma movimentação registrada recentemente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Alpine.js Webcam / Mobile Barcode Scanner Script -->
    <script>
        function custodyScanner() {
            return {
                isCameraActive: false,
                cameraStatus: 'Inicializando câmera...',
                stream: null,
                detector: null,
                scanInterval: null,

                async toggleCamera() {
                    if (this.isCameraActive) {
                        this.stopCamera();
                    } else {
                        await this.startCamera();
                    }
                },

                async startCamera() {
                    try {
                        this.isCameraActive = true;
                        this.cameraStatus = 'Aguardando permissão de câmera...';

                        const constraints = {
                            video: {
                                facingMode: { ideal: 'environment' },
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            }
                        };

                        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.$refs.videoElement.srcObject = this.stream;
                        this.cameraStatus = 'Câmera ativa. Aponte para um código de barras ou QR code.';

                        if ('BarcodeDetector' in window) {
                            this.detector = new BarcodeDetector({
                                formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'data_matrix']
                            });

                            this.scanInterval = setInterval(async () => {
                                if (!this.isCameraActive || !this.$refs.videoElement) return;
                                try {
                                    const barcodes = await this.detector.detect(this.$refs.videoElement);
                                    if (barcodes.length > 0) {
                                        const detectedCode = barcodes[0].rawValue;
                                        this.beep();
                                        this.cameraStatus = 'Código lido: ' + detectedCode;
                                        this.$wire.lookupInstrument(detectedCode);
                                        this.stopCamera();
                                    }
                                } catch (e) {
                                    // frame detection pass
                                }
                            }, 350);
                        } else {
                            this.cameraStatus = 'Aviso: API nativa BarcodeDetector indisponível neste navegador. Use leitor USB ou digite o código.';
                        }
                    } catch (err) {
                        this.isCameraActive = false;
                        this.cameraStatus = 'Erro ao acessar câmera: ' + err.message;
                        alert('Não foi possível acessar a câmera do dispositivo: ' + err.message);
                    }
                },

                stopCamera() {
                    if (this.scanInterval) {
                        clearInterval(this.scanInterval);
                        this.scanInterval = null;
                    }
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.isCameraActive = false;
                },

                beep() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.value = 880;
                        gain.gain.setValueAtTime(0.1, ctx.currentTime);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.12);
                    } catch (e) {
                        // audio context silent fail
                    }
                }
            }
        }
    </script>
</x-filament-panels::page>
