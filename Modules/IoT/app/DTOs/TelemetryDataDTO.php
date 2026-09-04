<?php

namespace Modules\IoT\DTOs;

class TelemetryDataDTO
{
    /**
     * @param  string  $tenantId  ID do Tenant
     * @param  string  $machineId  ID da Máquina
     * @param  string  $nodeId  ID do Nó de Borda
     * @param  int|null  $msgId  ID da mensagem sequencial
     * @param  int|null  $rpm  Rotações por minuto
     * @param  float  $rmsGlobal  Valor global de RMS
     * @param  array  $timeDomain  Métricas no domínio do tempo (rms_x, kurt_x, etc)
     * @param  float|null  $micRms  Métrica do microfone INMP441
     * @param  array  $features  Features extraídas na borda
     * @param  string  $mlStatus  Status preditivo (saudavel, falha, etc)
     * @param  float  $mlConfidence  Nível de confiança do modelo
     * @param  string|null $cloudMlStatus Status vindo da nuvem (modelo especialista)
     * @param  float|null $cloudMlConfidence Confiança vinda da nuvem
     * @param  string  $timestamp  ISO8601
     */
    public function __construct(
        public string $tenantId,
        public string $machineId,
        public string $nodeId,
        public ?int $msgId,
        public ?int $rpm,
        public float $rmsGlobal,
        public array $timeDomain,
        public ?float $micRms,
        public array $features,
        public string $mlStatus,
        public float $mlConfidence,
        public string $timestamp,
        public ?string $cloudMlStatus = null,
        public ?float $cloudMlConfidence = null
    ) {}

    public function toArray(): array
    {
        return [
            'machine_id' => $this->machineId,
            'node_id' => $this->nodeId,
            'msg_id' => $this->msgId,
            'rpm' => $this->rpm,
            'rms_global' => $this->rmsGlobal,
            'time_domain' => $this->timeDomain,
            'mic_rms' => $this->micRms,
            'features' => $this->features,
            'ml_status' => $this->mlStatus,
            'ml_confidence' => $this->mlConfidence,
            'cloud_ml_status' => $this->cloudMlStatus,
            'cloud_ml_confidence' => $this->cloudMlConfidence,
            'timestamp' => $this->timestamp,
        ];
    }
}
