<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MachineLearningService
{
    /**
     * Chama o microserviço FastAPI para analisar as features extraídas na borda.
     *
     * @param  array<string, mixed>  $features
     * @return array{
     *     status: string,
     *     confidence: float,
     *     prob_defect?: mixed
     * }
     */
    public function predictAnomalia(array $features): array
    {
        try {
            $url = (string) config('iot.ml_service_url', 'http://ml-service:8000/predict-anomalia');

            $numericFeatures = [];
            foreach ($features as $key => $value) {
                if (is_numeric($value)) {
                    $numericFeatures[(string) $key] = (float) $value;
                }
            }

            Log::channel('iot_ml')->info('Enviando features para Cloud ML', ['features_count' => count($numericFeatures)]);

            $response = Http::timeout(10)->post($url, [
                'features' => $numericFeatures,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if (is_array($result) && isset($result['status'])) {
                    $status = (string) $result['status'];
                    $confidence = (float) ($result['confidence'] ?? 0.0);

                    Log::channel('iot_ml')->info('Resposta recebida da Nuvem', [
                        'status' => $status,
                        'confidence' => $confidence,
                        'prob_defect' => $result['prob_defect'] ?? 'N/A',
                    ]);

                    return [
                        'status' => $status,
                        'confidence' => $confidence,
                        'prob_defect' => $result['prob_defect'] ?? null,
                    ];
                }
            }

            Log::channel('iot_ml')->error('Falha na resposta do Machine Learning Service (Features).', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Exception $exception) {
            Log::error("IoT: Erro de conexão com Machine Learning Service: {$exception->getMessage()}");
        }

        return [
            'status' => 'indeterminado',
            'confidence' => 0.0,
        ];
    }

    /**
     * @deprecated Usar predictAnomalia() com features.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     status: string,
     *     confidence: float,
     *     prob_defect?: mixed
     * }
     */
    public function analyzeTelemetry(array $payload): array
    {
        $features = is_array($payload['features'] ?? null) ? $payload['features'] : [];

        return $this->predictAnomalia($features);
    }
}
