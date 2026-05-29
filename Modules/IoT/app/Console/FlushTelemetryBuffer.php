<?php

namespace Modules\IoT\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Modules\IoT\Models\IoTSensorData;

class FlushTelemetryBuffer extends Command
{
    /**
     * @var string
     */
    protected $signature = 'iot:flush-buffer';

    /**
     * @var string
     */
    protected $description = 'Extrai dados de telemetria do Redis e realiza a inserção em massa no MySQL';

    /**
     * Executa o comando.
     */
    public function handle(): void
    {
        $this->info('Iniciando o Flush do buffer de telemetria...');

        $records = [];
        $batchSize = 500; // Limite por batch de insert

        // Busca todas as mensagens da lista no Redis
        while ($data = Redis::lpop('iot:telemetry_buffer')) {
            $records[] = json_decode($data, true);

            // Quando atingir o tamanho do batch, insere e limpa a memória
            if (count($records) >= $batchSize) {
                $this->insertBatch($records);
                $records = [];
            }
        }

        // Insere o restante
        if (count($records) > 0) {
            $this->insertBatch($records);
        }

        $this->info('Flush finalizado com sucesso.');
    }

    protected function insertBatch(array $records): void
    {
        try {
            // Usando Query Builder diretamente para máxima performance no batch insert
            IoTSensorData::insert($records);
            $this->info('Inseridos '.count($records).' registros.');
        } catch (\Exception $e) {
            $this->error('Erro ao inserir batch: '.$e->getMessage());

            // Em caso de erro, poderíamos re-enfileirar os registros ou logar para auditoria
        }
    }
}
