<?php

namespace Modules\IoT\DTOs;

class TelemetryDataDTO
{
    /**
     * @param  string  $tenantId  ID do Tenant
     * @param  string  $machineId  ID da Máquina
     * @param  string  $nodeId  ID do Nó de Borda
     * @param  int  $msgId  ID da mensagem sequencial
     * @param  int  $rpm  Rotações por minuto
     * @param  float  $rmsGlobal  Valor global de RMS
     * @param  array  $timeDomain  Métricas no domínio do tempo (rms_x, kurt_x, etc)
     * @param  array  $piezo  Métricas do sensor piezoelétrico
     * @param  string  $mlStatus  Status preditivo (saudavel, falha, etc)
     * @param  float  $mlConfidence  Nível de confiança do modelo
     * @param  string  $timestamp  ISO8601
     */
    public function __construct(
        public string $tenantId,
        public string $machineId,
        public string $nodeId,
        public int $msgId,
        public int $rpm,
        public float $rmsGlobal,
        public array $timeDomain,
        public array $piezo,
        public string $mlStatus,
        public float $mlConfidence,
        public string $timestamp
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
            'piezo' => $this->piezo,
            'ml_status' => $this->mlStatus,
            'ml_confidence' => $this->mlConfidence,
            'timestamp' => $this->timestamp,
        ];
    }
}
