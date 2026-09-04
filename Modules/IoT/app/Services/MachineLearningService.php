<?php

namespace Modules\IoT\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MachineLearningService
{
    /**
     * Chama o microserviço FastAPI para analisar as features extraídas na borda.
     * Retorna um array com o status preditivo e confiança.
     */
    public function predictAnomalia(array $features): array
    {
        try {
            // No Docker, o hostname é o nome do serviço definido no docker-compose
            $url = config('iot.ml_service_url', 'http://ml-service:8000/predict-anomalia');

            Log::channel('iot_ml')->info('Enviando features para Cloud ML', ['features_count' => count($features)]);

            $response = Http::timeout(10)->post($url, [
                'features' => $features,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::channel('iot_ml')->info('Resposta recebida da Nuvem', [
                    'status' => $result['status'],
                    'confidence' => $result['confidence'],
                    'prob_defect' => $result['prob_defect'] ?? 'N/A'
                ]);
                return $result;
            }

            Log::channel('iot_ml')->error('Falha na resposta do Machine Learning Service (Features).', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('IoT: Erro de conexão com Machine Learning Service: '.$e->getMessage());
        }

        // Fallback de segurança se o ML falhar
        return [
            'status' => 'indeterminado',
            'confidence' => 0.0,
        ];
    }

    /**
     * @deprecated Usar predictAnomalia() com features.
     */
    public function analyzeTelemetry(array $payload): array
    {
        return $this->predictAnomalia($payload['features'] ?? []);
    }
}
