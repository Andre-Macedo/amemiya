<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="p-4 rounded-full bg-primary-500/10 mb-4">
                <x-heroicon-o-document-magnifying-glass class="h-12 w-12 text-primary-600" />
            </div>
            
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Visualizador de Logs Industrial
            </h2>
            
            <p class="max-w-md mx-auto mt-2 text-gray-500">
                Acesse a interface completa para monitorar erros, avisos e atividades do servidor em tempo real.
            </p>
            
            <div class="mt-8">
                <x-filament::button 
                    tag="a" 
                    href="/log-viewer" 
                    target="_blank"
                    size="lg"
                    icon="heroicon-m-arrow-top-right-on-square"
                >
                    Abrir Painel de Logs
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
    
    <x-filament::section title="Informações Úteis">
        <ul class="list-disc list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li>Os logs são rotacionados diariamente para economizar espaço.</li>
            <li>Você pode filtrar por nível de erro (Emergency, Alert, Critical, Error, Warning).</li>
            <li>O sistema permite baixar arquivos de log antigos para auditoria.</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
