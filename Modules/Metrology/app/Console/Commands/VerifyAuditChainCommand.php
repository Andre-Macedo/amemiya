<?php

declare(strict_types=1);

namespace Modules\Metrology\Console\Commands;

use Illuminate\Console\Command;
use Modules\System\Services\AuditChainService;

class VerifyAuditChainCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'metrology:verify-audit-chain {--tenant= : ID opcional do Tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Verifica a integridade criptográfica da cadeia forense de auditoria (ISO/IEC 17025 e FDA 21 CFR Part 11)';

    public function handle(AuditChainService $auditChainService): int
    {
        $tenantId = $this->option('tenant');

        $this->info('========================================================================');
        $this->info('  VERIFICAÇÃO FORENSE DE INTEGRIDADE DA CADEIA DE AUDITORIA (SHA-256)   ');
        $this->info('  Conformidade Regulatória: ABNT NBR ISO/IEC 17025 & FDA 21 CFR Part 11  ');
        $this->info('========================================================================');

        if ($tenantId) {
            $this->line("Tenant Selecionado: {$tenantId}");
        } else {
            $this->line('Escopo: Global / Todos os Tenants');
        }

        $report = $auditChainService->verifyChain($tenantId ? (string) $tenantId : null);

        $this->newLine();
        $this->line("Total de Blocos Auditados: {$report['total_records']}");
        $this->line("Gênesis Hash: {$report['genesis_hash']}");
        $this->line('Chain Head Hash: ' . ($report['chain_head_hash'] ?? 'Nenhum registro'));
        $this->line("Data da Verificação: {$report['verified_at']}");
        $this->newLine();

        if ($report['is_valid']) {
            $this->info(' [OK] CADEIA 100% ÍNTEGRA: Nenhuma adulteração, deleção ou quebra detectada.');

            return self::SUCCESS;
        }

        $this->error(' [FALHA CRÍTICA] CADEIA COMPROMETIDA: Adulterações ou descontinuidades detectadas!');
        $this->newLine();

        foreach ($report['errors'] as $error) {
            $this->error(" -> [{$error['type']}] {$error['message']}");
        }

        return self::FAILURE;
    }
}
