<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\IoT\Http\Requests\StoreIoTGatewayRequest;
use Modules\IoT\Http\Requests\UpdateIoTGatewayRequest;
use Modules\IoT\Http\Resources\IoTGatewayApiResource;
use Modules\IoT\Models\IoTGateway;

class IoTGatewayApiController extends Controller
{
    /**
     * Lista todos os gateways de telemetria cadastrados.
     */
    public function index(): AnonymousResourceCollection
    {
        $gateways = IoTGateway::query()->latest()->get();

        return IoTGatewayApiResource::collection($gateways);
    }

    /**
     * Registra um novo gateway concentrador.
     */
    public function store(StoreIoTGatewayRequest $request): IoTGatewayApiResource
    {
        $gateway = IoTGateway::create($request->validated());

        return new IoTGatewayApiResource($gateway);
    }

    /**
     * Exibe os detalhes de um gateway específico.
     */
    public function show(IoTGateway $iotGateway): IoTGatewayApiResource
    {
        return new IoTGatewayApiResource($iotGateway);
    }

    /**
     * Atualiza dados e status operacional de um gateway.
     */
    public function update(UpdateIoTGatewayRequest $request, IoTGateway $iotGateway): IoTGatewayApiResource
    {
        $iotGateway->update($request->validated());

        return new IoTGatewayApiResource($iotGateway);
    }

    /**
     * Remove um gateway do cluster.
     */
    public function destroy(IoTGateway $iotGateway): Response
    {
        $iotGateway->delete();

        return response()->noContent();
    }
}

