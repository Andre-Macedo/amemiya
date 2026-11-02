<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IoT\Http\Requests\StoreIoTMLModelRequest;
use Modules\IoT\Http\Resources\IoTMLModelApiResource;
use Modules\IoT\Models\IoTMLModel;

class IoTMLModelApiController extends Controller
{
    /**
     * Lista todos os modelos cadastrados no Model Registry.
     */
    public function index(): AnonymousResourceCollection
    {
        $models = IoTMLModel::query()
            ->with(['dataset:id,name,type'])
            ->latest('created_at')
            ->get();

        return IoTMLModelApiResource::collection($models);
    }

    /**
     * Registra uma nova versão de modelo treinada (no Jupyter ou AutoML).
     */
    public function store(StoreIoTMLModelRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $model = IoTMLModel::create([
            'name' => $validated['name'],
            'model_type' => $validated['model_type'],
            'version' => $validated['version'],
            'target_device' => $validated['target_device'],
            'dataset_id' => $validated['dataset_id'] ?? null,
            'status' => $validated['status'] ?? 'candidate',
            'artifact_path' => $validated['artifact_path'] ?? null,
            'metrics' => $validated['metrics'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return (new IoTMLModelApiResource($model))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Promove um modelo para produção (ou modo sombra).
     */
    public function deploy(Request $request, string $id): JsonResponse
    {
        $model = IoTMLModel::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:in_production,shadow,deprecated',
        ]);

        // Se for promovido para produção, coloca os outros do mesmo tipo como obsoletos ou candidatos
        if ($validated['status'] === 'in_production') {
            IoTMLModel::where('model_type', $model->model_type)
                ->where('target_device', $model->target_device)
                ->where('status', 'in_production')
                ->where('id', '!=', $model->id)
                ->update(['status' => 'deprecated']);
        }

        $model->update([
            'status' => $validated['status'],
            'deployed_at' => now(),
        ]);

        return response()->json([
            'message' => "Modelo {$model->name} ({$model->version}) atualizado para status: {$model->status}.",
            'data' => new IoTMLModelApiResource($model),
        ]);
    }
}
