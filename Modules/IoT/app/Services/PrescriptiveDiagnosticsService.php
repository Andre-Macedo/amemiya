<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

class PrescriptiveDiagnosticsService
{
    /**
     * Limiares térmicos industriais padronizados para mancais de rolamento (SKF / ISO).
     */
    protected const TEMP_WARNING_CELSIUS = 75.0;

    protected const TEMP_CRITICAL_CELSIUS = 90.0;

    public function __construct(
        protected ISO20816SeverityService $isoService = new ISO20816SeverityService,
        protected BearingKinematicsService $kinematicsService = new BearingKinematicsService
    ) {}

    /**
     * Gera um diagnóstico prescritivo fundindo vibração mecânica, modelo de IA e limites térmicos.
     *
     * @param  array<string, mixed>  $isoEvaluation  Retorno de ISO20816SeverityService::evaluate
     * @param  string|null  $mlStatus  Veredito do modelo de IA (borda ou nuvem)
     * @param  float|null  $mlConfidence  Grau de confiança da predição (0.0 a 1.0)
     * @param  float|null  $tempCelsius  Temperatura medida na carcaça do mancal
     * @param  array<int, array{fault_match: string, amplitude: float}>  $matchedPeaks  Picos espectrais coincidentes
     * @return array{
     *     severity_zone: ?string,
     *     severity_label: string,
     *     thermal_status: string,
     *     root_cause: string,
     *     confidence: float,
     *     urgency_hours: int,
     *     recommended_actions: array<int, string>,
     *     summary_text: string
     * }
     */
    public function generateDiagnosis(
        array $isoEvaluation,
        ?string $mlStatus = null,
        ?float $mlConfidence = 0.0,
        ?float $tempCelsius = null,
        array $matchedPeaks = []
    ): array {
        $zone = $isoEvaluation['zone'] ?? 'A';
        $vRms = $isoEvaluation['velocity_rms'] ?? 0.0;

        // 1. Avaliação Térmica
        $thermalStatus = 'normal';
        if ($tempCelsius !== null) {
            if ($tempCelsius >= self::TEMP_CRITICAL_CELSIUS) {
                $thermalStatus = 'critical';
            } elseif ($tempCelsius >= self::TEMP_WARNING_CELSIUS) {
                $thermalStatus = 'warning';
            }
        }

        // 2. Extração de picos de defeito de rolamento
        $hasBpfo = false;
        $hasBpfi = false;
        $hasHigh1X = false;
        $hasHigh2X = false;

        foreach ($matchedPeaks as $peak) {
            $match = $peak['fault_match'] ?? '';
            if (str_contains($match, 'BPFO')) {
                $hasBpfo = true;
            } elseif (str_contains($match, 'BPFI')) {
                $hasBpfi = true;
            } elseif ($match === '1X_SHAFT') {
                $hasHigh1X = true;
            } elseif ($match === '2X_SHAFT') {
                $hasHigh2X = true;
            }
        }

        // 3. Determinação da Causa-Raiz
        $rootCause = 'Operação Mecânica Estável';
        $actions = ['Manter plano de monitoramento periódico por vibração.'];
        $urgencyHours = 720; // 30 dias

        if ($hasBpfo || $mlStatus === 'falha_rolamento_bpfo') {
            $rootCause = 'Defeito na Pista Externa do Rolamento (BPFO)';
            $actions = [
                'Inspecionar visualmente o anel externo e pista do rolamento.',
                'Verificar presença de contaminação ou lascamento na pista externa.',
                'Programar substituição preventiva do rolamento no próximo turno.',
            ];
            $urgencyHours = 48;
        } elseif ($hasBpfi || $mlStatus === 'falha_rolamento_bpfi') {
            $rootCause = 'Defeito na Pista Interna do Rolamento (BPFI)';
            $actions = [
                'Inspecionar acoplamento do eixo ao anel interno e folga radial.',
                'Verificar ajuste com interferência no eixo para descartar escorregamento.',
                'Programar substituição do rolamento.',
            ];
            $urgencyHours = 48;
        } elseif ($hasHigh2X || $mlStatus === 'desalinhamento') {
            $rootCause = 'Desalinhamento Angular/Paralelo de Eixo (Harmônico 2X)';
            $actions = [
                'Executar alinhamento a laser entre o motor e a carga.',
                'Verificar folgas e calços na base de fixação dos mancais.',
            ];
            $urgencyHours = 120;
        } elseif ($hasHigh1X || $mlStatus === 'desbalanceamento') {
            $rootCause = 'Desbalanceamento Dinâmico de Massa do Rotor (Harmônico 1X)';
            $actions = [
                'Executar balanceamento dinâmico de campo em 1 ou 2 planos.',
                'Limpar o rotor e verificar acúmulo de material incrustado ou quebra de palhetas.',
            ];
            $urgencyHours = 72;
        } elseif ($thermalStatus === 'critical' || $thermalStatus === 'warning') {
            $rootCause = 'Sobreaquecimento no Mancal por Atrito / Falta de Lubrificação';
            $actions = [
                'Verificar nível, viscosidade e intervalo de engraxamento do mancal.',
                'Avaliar se há sobrecarga axial ou pré-carga excessiva nos rolamentos.',
            ];
            $urgencyHours = $thermalStatus === 'critical' ? 12 : 48;
        } elseif ($zone === 'D') {
            $rootCause = 'Vibração Crítica Global Indeterminada (ISO 20816 Zona D)';
            $actions = [
                'Interromper operação imediatamente para inspeção de segurança.',
                'Verificar aperto de parafusos de base e folga de fixação.',
            ];
            $urgencyHours = 4;
        } elseif ($zone === 'C') {
            $rootCause = 'Vibração em Nível de Alerta (ISO 20816 Zona C)';
            $actions = [
                'Aumentar a frequência de amostragem da telemetria.',
                'Programar parada de manutenção para inspeção detalhada.',
            ];
            $urgencyHours = 72;
        }

        if ($thermalStatus === 'critical' && ! str_contains($rootCause, 'Sobreaquecimento')) {
            $actions[] = "ALERTA TÉRMICO CRÍTICO: Temperatura no mancal atingiu {$tempCelsius}°C (limite: 90°C).";
            $urgencyHours = min($urgencyHours, 12);
        }

        $summaryText = "Severidade: Zona {$zone} ({$vRms} mm/s) | Causa-Raiz: {$rootCause} | Ação: {$actions[0]}";

        return [
            'severity_zone' => $zone,
            'severity_label' => $isoEvaluation['label'] ?? "Zona {$zone}",
            'thermal_status' => $thermalStatus,
            'root_cause' => $rootCause,
            'confidence' => (float) max(0.60, $mlConfidence ?? 0.60),
            'urgency_hours' => $urgencyHours,
            'recommended_actions' => $actions,
            'summary_text' => $summaryText,
        ];
    }
}
