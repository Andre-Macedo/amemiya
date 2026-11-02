<?php

declare(strict_types=1);

namespace Modules\System\Console\Commands;

use Illuminate\Console\Command;
use Modules\System\Services\AuditChainService;
use Throwable;

class AnchorAuditChainCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:anchor-chain 
                            {--tenant= : ID ou Slug opcional do Tenant} 
                            {--type=WORM_IMMUTABLE : Tipo de ancoragem (WORM_IMMUTABLE, RFC3161_TSA)}';

    /**
     * The console command description.
     */
    protected $description = 'Ancola o cabeçalho criptográfico da cadeia de auditoria em storage WORM imutável (ISO 17025 / FDA 21 CFR Part 11)';

    public function handle(AuditChainService $auditChainService): int
    {
        $tenantId = $this->option('tenant');
        $anchorType = (string) ($this->option('type') ?: 'WORM_IMMUTABLE');

        $this->info('========================================================================');
        $this->info('  ANCORAGEM FORENSE DO CABEÇALHO DA CADEIA DE AUDITORIA (WORM / TSA)   ');
        $this->info('  Conformidade Regulatória: ABNT NBR ISO/IEC 17025 & FDA 21 CFR Part 11  ');
        $this->info('========================================================================');

        if ($tenantId) {
            $this->line("Tenant Selecionado: {$tenantId}");
        } else {
            $this->line('Escopo: Global / Padrão');
        }

        try {
            $anchor = $auditChainService->anchorChain(
                $tenantId ? (string) $tenantId : null,
                $anchorType
            );

            if ($anchor === null) {
                $this->warn(' [AVISO] Nenhum registro de auditoria encontrado para ancorar.');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->info(' [OK] ANCORAGEM CONCLUÍDA COM SUCESSO!');
            $this->line("Anchor ID: {$anchor->id}");
            $this->line("Sequência Ancorada: #{$anchor->sequence_number}");
            $this->line("Chain Head Hash: {$anchor->chain_head_hash}");
            $this->line("Manifest Hash: {$anchor->manifest_hash}");
            $this->line("Storage Path: {$anchor->storage_path}");
            $this->line("Total de Registros Cobertos: {$anchor->total_records}");
            $this->line("Data da Ancoragem: {$anchor->anchored_at}");
            $this->newLine();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(" [FALHA CRÍTICA] Erro ao ancorar cadeia de auditoria: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
