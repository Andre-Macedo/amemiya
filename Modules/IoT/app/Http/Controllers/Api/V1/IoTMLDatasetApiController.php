<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTMLBurst;
use Modules\IoT\Models\IoTMLDataset;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IoTMLDatasetApiController extends Controller
{
    /**
     * Lista exata das 36 features na ordem canônica esperada pelo modelo XGBoost.
     */
    public const CANONICAL_FEATURES = [
        'z_rms', 'z_kurtosis', 'z_skewness', 'z_energy_band_1', 'z_peak_band_1', 'z_energy_band_2', 'z_energy_band_3', 'z_energy_band_4',
        'y_rms', 'y_kurtosis', 'y_skewness', 'y_energy_band_1', 'y_peak_band_1', 'y_energy_band_2', 'y_energy_band_3', 'y_energy_band_4',
        'x_rms', 'x_kurtosis', 'x_skewness', 'x_energy_band_1', 'x_peak_band_1', 'x_energy_band_2', 'x_energy_band_3', 'x_energy_band_4',
        'mic_rms', 'mic_crest_factor', 'mic_energy_0_500', 'mic_peak_0_500', 'mic_energy_500_2000', 'mic_peak_500_2000',
        'mic_energy_2000_5000', 'mic_peak_2000_5000', 'mic_energy_5000_10000', 'mic_peak_5000_10000', 'mic_energy_above_10000', 'mic_peak_above_10000',
    ];

    /**
     * Lista os datasets cadastrados no tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $datasets = IoTMLDataset::query()
            ->with(['targetMachine:id,name,code'])
            ->withCount(['bursts', 'models'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $datasets,
        ]);
    }

    /**
     * Cria um novo Dataset (Supervisionado ou Baseline de Borda).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:diagnostic_multiclass,baseline_normal,benchmark_golden_set,run_to_failure,supervised_xgboost,unsupervised_iforest',
            'target_machine_id' => 'nullable|string|exists:machines,id',
            'description' => 'nullable|string|max:1000',
        ]);

        $dataset = IoTMLDataset::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.substr((string) Str::ulid(), -6),
            'type' => $validated['type'],
            'target_machine_id' => $validated['target_machine_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'collecting',
        ]);

        return response()->json([
            'message' => 'Dataset criado com sucesso.',
            'data' => $dataset,
        ], 201);
    }

    /**
     * Detalhes de um dataset específico com resumo das amostras.
     */
    public function show(string $id): JsonResponse
    {
        $dataset = IoTMLDataset::with(['targetMachine:id,name,code', 'models'])
            ->withCount('bursts')
            ->findOrFail($id);

        $recentBursts = $dataset->bursts()
            ->with(['node:id,name,node_id', 'validator:id,name'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $dataset,
            'recent_bursts' => $recentBursts,
        ]);
    }

    /**
     * Rotulação A Posteriori: Transforma um log de diagnóstico em amostra curada de treino.
     */
    public function labelLog(Request $request, string $logId): JsonResponse
    {
        $validated = $request->validate([
            'ground_truth_label' => 'required|string|in:saudavel,desbalanceamento,folga_mecanica,falha_rolamento,falso_positivo,falso_positivo_operacional,descarte_outlier',
            'dataset_id' => 'nullable|string|exists:iot_ml_datasets,id',
            'session_id' => 'nullable|string|max:100',
        ]);

        $log = IoTDeviceLog::with(['node', 'machine'])->findOrFail($logId);

        // Se o operador optou por descartar como ruído/choque espúrio, não polui datasets de treino
        if ($validated['ground_truth_label'] === 'descarte_outlier') {
            return response()->json([
                'message' => 'Evento marcado como ruído externo espúrio e descartado sem poluir o baseline.',
                'data' => [
                    'log_id' => $log->id,
                    'status' => 'discarded',
                ],
            ]);
        }

        // Falso alarme operacional legítimo é mapeado para 'saudavel' como Hard Negative
        $isHardNegative = $validated['ground_truth_label'] === 'falso_positivo_operacional';
        $finalGroundTruth = $isHardNegative ? 'saudavel' : $validated['ground_truth_label'];

        // Se o dataset não foi informado, busca ou cria um dataset padrão para a máquina
        $dataset = null;
        if (! empty($validated['dataset_id'])) {
            $dataset = IoTMLDataset::findOrFail($validated['dataset_id']);
        } else {
            $datasetType = ($finalGroundTruth === 'saudavel' && $request->boolean('is_baseline'))
                ? 'baseline_normal'
                : 'diagnostic_multiclass';

            $typeLabel = $datasetType === 'baseline_normal' ? 'Linha de Base' : 'Diagnóstico Multiclasse';

            $dataset = IoTMLDataset::firstOrCreate(
                [
                    'tenant_id' => $log->tenant_id,
                    'type' => $datasetType,
                    'target_machine_id' => $log->machine_id,
                ],
                [
                    'name' => ($log->machine?->name ?? 'Geral').' - Dataset '.$typeLabel,
                    'slug' => Str::slug(($log->machine?->name ?? 'geral').'-'.$datasetType).'-'.substr((string) Str::ulid(), -6),
                    'description' => 'Dataset criado automaticamente a partir da triagem de eventos de campo.',
                    'status' => 'collecting',
                ]
            );
        }

        // Cria ou atualiza a rajada correspondente ao log
        $burst = IoTMLBurst::updateOrCreate(
            [
                'device_log_id' => $log->id,
            ],
            [
                'tenant_id' => $log->tenant_id,
                'dataset_id' => $dataset->id,
                'node_id' => $log->node_id,
                'machine_id' => $log->machine_id,
                'session_id' => $validated['session_id'] ?? ('burst_'.now()->format('Ymd_His')),
                'origin' => $isHardNegative ? 'hard_negative_triaged' : 'drawer_triaged',
                'rpm' => $log->rpm,
                'rms_global' => $log->rms_global,
                'windows_count' => 1,
                'predicted_label' => $log->cloud_ml_status ?? $log->ml_status,
                'predicted_confidence' => $log->cloud_ml_confidence ?? $log->ml_confidence,
                'ground_truth_label' => $finalGroundTruth,
                'is_validated' => true,
                'validated_by_user_id' => auth()->id(),
                'validated_at' => now(),
                'features_summary' => $log->features,
            ]
        );

        // Atualiza estatísticas do dataset
        $dataset->recalculateMetrics();

        return response()->json([
            'message' => 'Evento rotulado com sucesso e integrado ao dataset.',
            'data' => [
                'burst' => $burst,
                'dataset' => $dataset->fresh(),
            ],
        ]);
    }

    /**
     * Exporta o Dataset formatado em CSV pronto para o Jupyter Notebook / Pandas.
     */
    public function export(string $id): StreamedResponse
    {
        $dataset = IoTMLDataset::findOrFail($id);

        $bursts = $dataset->bursts()
            ->where('is_validated', true)
            ->whereNotNull('ground_truth_label')
            ->orderBy('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$dataset->slug}_export.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($bursts) {
            $handle = fopen('php://output', 'w');

            // Cabeçalho CSV: Metadados + 36 Features + Target
            $headerRow = array_merge(
                ['burst_id', 'session_id', 'machine_id', 'rpm', 'rms_global', 'target_label'],
                self::CANONICAL_FEATURES
            );
            fputcsv($handle, $headerRow);

            foreach ($bursts as $burst) {
                $feat = $burst->features_summary ?? [];

                // Normaliza o rótulo para classe numérica / string
                $row = [
                    $burst->id,
                    $burst->session_id ?? $burst->id,
                    $burst->machine_id ?? 'N/A',
                    $burst->rpm ?? 0,
                    $burst->rms_global ?? 0.0,
                    $burst->ground_truth_label,
                ];

                foreach (self::CANONICAL_FEATURES as $featKey) {
                    $row[] = (float) ($feat[$featKey] ?? 0.0);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
