<?php

namespace Modules\IoT\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MachineLearningService
{
    /**
     * Chama o microserviço FastAPI para analisar os dados brutos de vibração e acústica.
     * Retorna um array com o status preditivo, confiança e o espectrograma em Base64.
     */
    public function analyzeTelemetry(array $payload): array
    {
        try {
            // No Docker, o hostname é o nome do serviço definido no docker-compose
            $url = config('iot.ml_service_url', 'http://ml-service:8000/predict-anomalia');

            $response = Http::timeout(10)->post($url, [
                'radial' => $payload['radial'] ?? [],
                'tangential' => $payload['tangential'] ?? [],
                'axial' => $payload['axial'] ?? [],
                'microphone' => $payload['microphone'] ?? [],
                'inicio_janela' => $payload['inicio_janela'] ?? 0,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('IoT: Falha na resposta do Machine Learning Service.', [
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
            'spectrogram_b64' => null,
        ];
    }
}
