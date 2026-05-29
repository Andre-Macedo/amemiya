<?php

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IoT\Models\IoTGateway;
use Illuminate\Http\Resources\Json\JsonResource;

class IoTGatewayApiController extends Controller
{
    public function index()
    {
        $gateways = IoTGateway::query()->latest()->get();
        return JsonResource::collection($gateways);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_id' => 'required|string|unique:iot_gateways,device_id',
            'station_id' => 'nullable|exists:stations,id',
            'status' => 'required|string',
        ]);

        $gateway = IoTGateway::create($validated);

        return new JsonResource($gateway);
    }

    public function show(IoTGateway $iotGateway)
    {
        return new JsonResource($iotGateway);
    }

    public function update(Request $request, IoTGateway $iotGateway)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_id' => 'required|string|unique:iot_gateways,device_id,' . $iotGateway->id,
            'station_id' => 'nullable|exists:stations,id',
            'status' => 'required|string',
        ]);

        $iotGateway->update($validated);

        return new JsonResource($iotGateway);
    }

    public function destroy(IoTGateway $iotGateway)
    {
        $iotGateway->delete();
        return response()->noContent();
    }
}
