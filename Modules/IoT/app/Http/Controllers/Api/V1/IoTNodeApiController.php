<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\IoT\Http\Requests\StoreIoTNodeRequest;
use Modules\IoT\Http\Requests\UpdateIoTNodeRequest;
use Modules\IoT\Http\Resources\IoTNodeApiResource;
use Modules\IoT\Models\IoTNode;

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
}
