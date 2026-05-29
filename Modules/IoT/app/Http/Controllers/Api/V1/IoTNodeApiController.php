<?php

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IoT\Models\IoTNode;
use Illuminate\Http\Resources\Json\JsonResource;

class IoTNodeApiController extends Controller
{
    public function index()
    {
        $nodes = IoTNode::with(['gateway', 'machine'])->latest()->get();
        return JsonResource::collection($nodes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gateway_id' => 'required|exists:iot_gateways,id',
            'machine_id' => 'required|exists:machines,id',
            'name' => 'required|string|max:255',
            'node_id' => 'required|string',
            'status' => 'required|string',
        ]);

        $node = IoTNode::create($validated);

        return new JsonResource($node);
    }

    public function show(IoTNode $iotNode)
    {
        return new JsonResource($iotNode->load(['gateway', 'machine']));
    }

    public function update(Request $request, IoTNode $iotNode)
    {
        $validated = $request->validate([
            'gateway_id' => 'required|exists:iot_gateways,id',
            'machine_id' => 'required|exists:machines,id',
            'name' => 'required|string|max:255',
            'node_id' => 'required|string',
            'status' => 'required|string',
        ]);

        $iotNode->update($validated);

        return new JsonResource($iotNode);
    }

    public function destroy(IoTNode $iotNode)
    {
        $iotNode->delete();
        return response()->noContent();
    }
}
