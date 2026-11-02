<?php

declare(strict_types=1);

namespace Modules\System\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Storage;
use Modules\System\Models\AuditChainAnchor;
use RuntimeException;

class AuditChainService
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Calcula o hash SHA-256 criptográfico canônico de um registro de auditoria.
     */
    public function computePayloadHash(Activity|array $log): string
    {
        if ($log instanceof Activity) {
            $seq = (int) ($log->sequence_number ?? 0);
            $prev = (string) ($log->previous_hash ?? self::GENESIS_HASH);
            $tenant = $log->tenant_id ? (string) $log->tenant_id : null;
            $logName = $log->log_name ? (string) $log->log_name : null;
            $desc = (string) $log->description;
            $subjectType = $log->subject_type ? (string) $log->subject_type : null;
            $subjectId = $log->subject_id !== null ? (string) $log->subject_id : null;
            $causerId = $log->causer_id !== null ? (string) $log->causer_id : null;
            $properties = $log->properties ? $log->properties->toArray() : [];
            $createdAt = $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null;
        } else {
            $seq = (int) ($log['sequence_number'] ?? 0);
            $prev = (string) ($log['previous_hash'] ?? self::GENESIS_HASH);
            $tenant = isset($log['tenant_id']) ? (string) $log['tenant_id'] : null;
            $logName = isset($log['log_name']) ? (string) $log['log_name'] : null;
            $desc = (string) ($log['description'] ?? '');
            $subjectType = isset($log['subject_type']) ? (string) $log['subject_type'] : null;
            $subjectId = isset($log['subject_id']) ? (string) $log['subject_id'] : null;
            $causerId = isset($log['causer_id']) ? (string) $log['causer_id'] : null;
            $properties = is_array($log['properties'] ?? null) ? $log['properties'] : [];
            $createdAt = isset($log['created_at']) ? (is_string($log['created_at']) ? $log['created_at'] : $log['created_at']->format('Y-m-d H:i:s')) : null;
        }

        // Ordenação canônica das chaves para garantir determinismo estrito
        ksort($properties);

        $payload = [
            'seq' => $seq,
            'prev' => $prev,
            'tenant' => $tenant,
            'log' => $logName,
            'desc' => $desc,
            'subj_type' => $subjectType,
            'subj_id' => $subjectId,
            'causer' => $causerId,
            'props' => $properties,
            'created' => $createdAt,
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Vincula criptograficamente o registro de log ao bloco anterior na cadeia (Blockchain audit trail).
     */
    public function chainRecord(Activity $activity): void
    {
        // Se já tiver sequência e hash definidos manualmente (ex: em migrações ou seeds específicos), respeita
        if ($activity->sequence_number !== null && $activity->record_hash !== null) {
            return;
        }

        $tenantId = $activity->tenant_id;

        // Busca o último bloco na cadeia para este tenant
        $lastRecord = Activity::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->whereNotNull('sequence_number')
            ->orderByDesc('sequence_number')
            ->first();

        if ($lastRecord === null) {
            $activity->sequence_number = 1;
            $activity->previous_hash = self::GENESIS_HASH;
        } else {
            $activity->sequence_number = (int) $lastRecord->sequence_number + 1;
            $activity->previous_hash = $lastRecord->record_hash ?? self::GENESIS_HASH;
        }

        if ($activity->created_at === null) {
            $activity->created_at = now();
        }

        $activity->record_hash = $this->computePayloadHash($activity);
    }

    /**
     * Executa a verificação forense completa da cadeia criptográfica de auditoria.
     * Detecta adulterações manuais de dados, exclusões de registros e quebras na cadeia.
     *
     * @return array<string, mixed>
     */
    public function verifyChain(?string $tenantId = null): array
    {
        $records = Activity::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->orderBy('sequence_number', 'asc')
            ->get();

        $total = $records->count();

        if ($total === 0) {
            return [
                'is_valid' => true,
                'total_records' => 0,
                'status' => 'empty',
                'genesis_hash' => self::GENESIS_HASH,
                'chain_head_hash' => null,
                'latest_sequence' => 0,
                'verified_at' => now()->toIso8601String(),
                'errors' => [],
            ];
        }

        $errors = [];
        $expectedPrevHash = self::GENESIS_HASH;
        $expectedSequence = 1;

        /** @var Activity|null $prevRecord */
        $prevRecord = null;

        foreach ($records as $index => $record) {
            $seq = (int) ($record->sequence_number ?? 0);
            $prevHash = (string) ($record->previous_hash ?? '');
            $recHash = (string) ($record->record_hash ?? '');

            // 1. Verificação de descontinuidade de sequência (exclusão de registro intermediário)
            if ($seq !== $expectedSequence) {
                $errors[] = [
                    'type' => 'SEQUENCE_GAP',
                    'record_id' => (string) $record->id,
                    'expected_sequence' => $expectedSequence,
                    'actual_sequence' => $seq,
                    'message' => "Descontinuidade na cadeia: registro com sequência esperada #{$expectedSequence} não encontrado (recebido #{$seq}). Possível exclusão não autorizada de log.",
                ];
            }

            // 2. Verificação de encadeamento do bloco (previous_hash deve bater exatamente com o bloco anterior)
            if ($prevHash !== $expectedPrevHash) {
                $errors[] = [
                    'type' => 'BROKEN_CHAIN_LINK',
                    'record_id' => (string) $record->id,
                    'sequence' => $seq,
                    'expected_previous_hash' => $expectedPrevHash,
                    'actual_previous_hash' => $prevHash,
                    'message' => "Elo quebrado no bloco #{$seq}: previous_hash diverge do record_hash do bloco precedente.",
                ];
            }

            // 3. Verificação de integridade dos dados (recalcula o hash canônico do registro)
            $recomputedHash = $this->computePayloadHash($record);
            if ($recHash !== $recomputedHash) {
                $errors[] = [
                    'type' => 'DATA_TAMPERING_DETECTED',
                    'record_id' => (string) $record->id,
                    'sequence' => $seq,
                    'expected_record_hash' => $recomputedHash,
                    'stored_record_hash' => $recHash,
                    'message' => "Adulteração de dados detectada no bloco #{$seq}: os dados do log foram modificados diretamente na base após a gravação original.",
                ];
            }

            $expectedPrevHash = $recHash;
            $expectedSequence = $seq + 1;
            $prevRecord = $record;
        }

        return [
            'is_valid' => empty($errors),
            'total_records' => $total,
            'status' => empty($errors) ? 'HEALTHY' : 'COMPROMISED',
            'genesis_hash' => self::GENESIS_HASH,
            'chain_head_hash' => $prevRecord?->record_hash,
            'latest_sequence' => $prevRecord?->sequence_number,
            'verified_at' => now()->toIso8601String(),
            'errors' => $errors,
        ];
    }

    /**
     * Ancola o cabeçalho atual da cadeia criptográfica de auditoria em manifesto WORM imutável e no banco.
     * Exige que a cadeia esteja 100% íntegra (HEALTHY).
     *
     * @throws RuntimeException se a cadeia estiver comprometida.
     */
    public function anchorChain(?string $tenantId = null, string $anchorType = 'WORM_IMMUTABLE'): ?AuditChainAnchor
    {
        $verification = $this->verifyChain($tenantId);

        if (! $verification['is_valid']) {
            throw new RuntimeException('Não é permitido ancorar uma cadeia de auditoria corrompida ou comprometida.');
        }

        if ($verification['total_records'] === 0) {
            return null;
        }

        $sequence = (int) $verification['latest_sequence'];
        $headHash = (string) $verification['chain_head_hash'];
        $total = (int) $verification['total_records'];
        $anchoredAt = now();

        $manifest = [
            'anchor_version' => '1.0',
            'tenant_id' => $tenantId,
            'sequence_number' => $sequence,
            'chain_head_hash' => $headHash,
            'total_records' => $total,
            'anchor_type' => $anchorType,
            'anchored_at' => $anchoredAt->toIso8601String(),
        ];

        ksort($manifest);
        $manifestHash = hash('sha256', (string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $tenantSubdir = $tenantId ? "tenants/{$tenantId}" : 'global';
        $shortHash = substr($manifestHash, 0, 16);
        $storagePath = "{$tenantSubdir}/audit_anchors/anchor_seq_{$sequence}_{$shortHash}.json";

        $fullWormPayload = array_merge($manifest, [
            'manifest_hash' => $manifestHash,
            'signature_scheme' => 'SHA256_RFC3161_COMPATIBLE',
        ]);

        Storage::disk('local')->put($storagePath, (string) json_encode($fullWormPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return AuditChainAnchor::create([
            'tenant_id' => $tenantId,
            'sequence_number' => $sequence,
            'chain_head_hash' => $headHash,
            'total_records' => $total,
            'anchor_type' => $anchorType,
            'storage_path' => $storagePath,
            'manifest_hash' => $manifestHash,
            'manifest_payload' => $fullWormPayload,
            'anchored_at' => $anchoredAt,
        ]);
    }

    /**
     * Valida a integridade forense de um manifesto de ancoragem WORM gravado no disco contra o banco de dados.
     *
     * @return array<string, mixed>
     */
    public function verifyAnchor(AuditChainAnchor $anchor): array
    {
        $storagePath = (string) $anchor->storage_path;
        $fileExists = Storage::disk('local')->exists($storagePath);

        if (! $fileExists) {
            return [
                'is_valid' => false,
                'status' => 'STORAGE_FILE_MISSING',
                'message' => "O arquivo de manifesto WORM [{$storagePath}] não foi localizado no storage.",
            ];
        }

        $fileContent = Storage::disk('local')->get($storagePath);
        $payload = json_decode((string) $fileContent, true);

        if (! is_array($payload) || ! isset($payload['manifest_hash'])) {
            return [
                'is_valid' => false,
                'status' => 'MALFORMED_MANIFEST',
                'message' => 'O manifesto de ancoragem no storage está corrompido ou malformado.',
            ];
        }

        $storedManifestHash = (string) $payload['manifest_hash'];
        unset($payload['manifest_hash'], $payload['signature_scheme']);
        ksort($payload);

        $recomputedManifestHash = hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if ($recomputedManifestHash !== $storedManifestHash || $recomputedManifestHash !== $anchor->manifest_hash) {
            return [
                'is_valid' => false,
                'status' => 'TAMPERED_MANIFEST',
                'message' => 'O manifesto WORM de ancoragem foi adulterado no storage.',
            ];
        }

        return [
            'is_valid' => true,
            'status' => 'HEALTHY',
            'manifest_hash' => $storedManifestHash,
            'sequence_number' => $anchor->sequence_number,
            'chain_head_hash' => $anchor->chain_head_hash,
            'storage_path' => $storagePath,
        ];
    }
}
