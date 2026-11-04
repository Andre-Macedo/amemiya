<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Modules\IoT\Http\Requests\StoreIoTNodeRequest;
use Modules\IoT\Http\Requests\UpdateIoTNodeRequest;
use Modules\IoT\Http\Resources\IoTNodeApiResource;
use Modules\IoT\Models\IoTNode;
use Modules\IoT\Services\BearingKinematicsService;

class IoTNodeApiController extends Controller
{
    /**
     * Lista todos os nós sensores vinculados ao tenant.
     */
    public function index(): AnonymousResourceCollection
    {
        $nodes = IoTNode::with(['gateway', 'machine'])->latest()->get();

        return IoTNodeApiResource::collection($nodes);
    }

    /**
     * Cadastra um novo nó sensor industrial.
     */
    public function store(StoreIoTNodeRequest $request): IoTNodeApiResource
    {
        $node = IoTNode::create($request->validated());

        return new IoTNodeApiResource($node);
    }

    /**
     * Exibe os detalhes de um nó sensor específico.
     */
    public function show(IoTNode $iotNode): IoTNodeApiResource
    {
        return new IoTNodeApiResource($iotNode->load(['gateway', 'machine']));
    }

    /**
     * Atualiza o cadastro e parâmetros de um nó sensor.
     */
    public function update(UpdateIoTNodeRequest $request, IoTNode $iotNode): IoTNodeApiResource
    {
        $iotNode->update($request->validated());

        return new IoTNodeApiResource($iotNode);
    }

    /**
     * Remove um nó sensor do sistema.
     */
    public function destroy(IoTNode $iotNode): Response
    {
        $iotNode->delete();

        return response()->noContent();
    }

    /**
     * Retorna o burst espectral mais recente em cache e as frequências de rolamento calculadas.
     */
    public function spectrum(IoTNode $iotNode, BearingKinematicsService $kinematicsService): JsonResponse
    {
        $cachedRaw = null;
        try {
            $cachedRaw = Redis::get("iot:node:{$iotNode->node_id}:spectrum:latest");
        } catch (\Throwable) {
            // Em testes ou sem Redis ativo
        }

        $spectrum = $cachedRaw ? json_decode((string) $cachedRaw, true) : null;

        $rpm = (float) ($spectrum['rpm'] ?? $iotNode->machine?->metadata['nominal_rpm'] ?? 1750.0);
        $bearingModel = (string) ($iotNode->machine?->metadata['bearing_model'] ?? 'skf_6204');

        $bearingKinematics = null;
        try {
            $bearingKinematics = $kinematicsService->calculateForPreset($bearingModel, $rpm);
        } catch (\Throwable) {
            // Ignora se o preset não existir
        }

        return new JsonResponse([
            'node_id' => $iotNode->node_id,
            'machine_id' => $iotNode->machine_id,
            'has_cached_spectrum' => $spectrum !== null,
            'spectrum' => $spectrum,
            'bearing_kinematics' => $bearingKinematics,
            'available_presets' => array_keys($kinematicsService->getPresets()),
        ]);
    }
}
