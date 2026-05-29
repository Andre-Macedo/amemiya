<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Última verificação realizada em: {{ $lastRanAt?->format('d/m/Y H:i:s') ?? 'Nunca executada' }}
            </p>
            
            <x-filament::button 
                color="primary" 
                tag="a" 
                href="#"
                wire:click="$refresh"
            >
                Atualizar Agora
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @if($checkResults)
                @foreach($checkResults->storedCheckResults as $result)
                    <x-filament::section>
                        <div class="flex items-start gap-4">
                            <div @class([
                                'p-2 rounded-lg',
                                'bg-success-500/10 text-success-600' => $result->status === 'ok',
                                'bg-warning-500/10 text-warning-600' => $result->status === 'warning',
                                'bg-danger-500/10 text-danger-600' => $result->status === 'failed',
                            ])>
                                @if($result->status === 'ok')
                                    <x-heroicon-o-check-circle class="h-6 w-6" />
                                @elseif($result->status === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                                @else
                                    <x-heroicon-o-x-circle class="h-6 w-6" />
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold truncate">{{ $result->label }}</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $result->shortSummary }}
                                </p>
                                @if($result->notificationMessage)
                                    <p class="text-xs mt-2 text-gray-400 italic">
                                        {{ $result->notificationMessage }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            @else
                <div class="col-span-full py-12 text-center text-gray-500">
                    Nenhum resultado de saúde encontrado. Por favor, execute os cheques de saúde.
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
