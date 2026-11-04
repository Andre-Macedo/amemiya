<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\System\Http\Requests\SendMachineCommandRequest;
use Modules\System\Http\Requests\StoreMachineRequest;
use Modules\System\Http\Requests\UpdateMachineRequest;
use Modules\System\Http\Resources\MachineApiResource;
use Modules\System\Models\Machine;
use PhpMqtt\Client\Facades\MQTT;

class MachineApiController extends Controller
{
    /**
     * Lista todas as máquinas e ativos industriais cadastrados.
     */
    public function index(): AnonymousResourceCollection
    {
        $machines = Machine::query()->with(['station'])->latest()->get();

        return MachineApiResource::collection($machines);
    }

    /**
     * Cadastra uma nova máquina monitorada.
     */
    public function store(StoreMachineRequest $request): MachineApiResource
    {
        $machine = Machine::create($request->validated());

        return new MachineApiResource($machine->load(['station']));
    }

    /**
     * Exibe os detalhes de uma máquina, posto de trabalho e sensores IoT vinculados.
     */
    public function show(Machine $machine): MachineApiResource
    {
        $machine->load(['station', 'nodes']);

        return new MachineApiResource($machine);
    }

    /**
     * Atualiza os dados ou metadados de classificação ISO da máquina.
     */
    public function update(UpdateMachineRequest $request, Machine $machine): MachineApiResource
    {
        $machine->update($request->validated());

        return new MachineApiResource($machine->load(['station']));
    }

    /**
     * Remove uma máquina do sistema (SoftDelete).
     */
    public function destroy(Machine $machine): Response
    {
        $machine->delete();

        return response()->noContent();
    }

    /**
     * Dispara comando de atuação remota para a máquina e nós vinculados via MQTT.
     */
    public function sendCommand(SendMachineCommandRequest $request, Machine $machine): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $machine->load(['nodes.gateway']);

        $command = (string) $validated['command'];
        $commandId = (string) Str::uuid();

        $payload = [
            'command_id' => $commandId,
            'machine_id' => $machine->id,
            'machine_code' => $machine->code,
            'command' => $command,
            'rpm' => $validated['rpm'] ?? null,
            'relay_index' => $validated['relay_index'] ?? null,
            'relay_state' => $validated['relay_state'] ?? null,
            'state' => $validated['state'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'operator' => $user?->name ?? 'Operador Remoto',
            'operator_id' => $user?->id,
            'timestamp' => now()->toIso8601String(),
        ];

        // Atualização de estado da máquina e alarme sonoro
        $metadata = $machine->metadata ?? [];
        if ($command === 'silence_buzzer') {
            $metadata['buzzer_active'] = false;
            $metadata['buzzer_silenced'] = true;
            $metadata['buzzer_silenced_at'] = now()->toIso8601String();
            $machine->update(['metadata' => $metadata]);
        } elseif ($command === 'reset_alarm') {
            $metadata['buzzer_active'] = false;
            $metadata['buzzer_silenced'] = false;
            $metadata['alarm_active'] = false;
            $machine->update(['metadata' => $metadata]);
        } elseif ($command === 'emergency_stop') {
            $machine->update(['status' => 'maintenance']);
        } elseif ($command === 'start') {
            $machine->update(['status' => 'operational']);
        }

        if (isset($validated['rpm']) && $validated['rpm'] > 0) {
            $metadata['nominal_rpm'] = (int) $validated['rpm'];
            $metadata['spindle_rpm'] = (int) $validated['rpm'];
            $machine->update(['metadata' => $metadata]);
        }

        // Registro de Auditoria e Rastreabilidade Metrológica
        $primaryNode = $machine->nodes->first();
        IoTDeviceLog::create([
            'tenant_id' => $machine->tenant_id,
            'machine_id' => $machine->id,
            'node_id' => $primaryNode?->id,
            'gateway_id' => $primaryNode?->gateway_id,
            'level' => ($command === 'emergency_stop') ? 'critical' : 'info',
            'event_type' => 'command_dispatched',
            'sent_command' => $payload,
            'message' => "Comando {$command} disparado pelo app por ".($user?->name ?? 'Operador'),
            'measured_at' => now(),
        ]);

        // Disparo via MQTT Broker
        $topicsPublished = [];
        try {
            $pubClientId = 'amemiya_cmd_'.substr(md5(uniqid()), 0, 6);
            $mqtt = MQTT::connection('default', $pubClientId);

            foreach ($machine->nodes as $node) {
                if ($node->gateway?->device_id && $node->node_id) {
                    $topic = "v1/gateways/{$node->gateway->device_id}/nodes/{$node->node_id}/commands";
                    $mqtt->publish($topic, json_encode($payload));
                    $topicsPublished[] = $topic;
                }
            }

            if ($machine->code) {
                $machineTopic = "machines/{$machine->code}/commands";
                $mqtt->publish($machineTopic, json_encode($payload));
                $topicsPublished[] = $machineTopic;
            }

            $mqtt->disconnect();
        } catch (\Throwable $e) {
            Log::channel('iot_commands')->warning('Broker MQTT inacessível para envio imediato: '.$e->getMessage(), [
                'command_id' => $commandId,
                'machine' => $machine->code,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comando despachado com sucesso.',
            'command_id' => $commandId,
            'command' => $command,
            'machine_id' => $machine->id,
            'machine_status' => $machine->status,
            'topics' => $topicsPublished,
            'payload' => $payload,
            'dispatched_at' => now()->toIso8601String(),
        ]);
    }
}
