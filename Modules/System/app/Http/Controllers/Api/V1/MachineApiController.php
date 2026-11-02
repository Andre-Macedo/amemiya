<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\System\Http\Requests\StoreMachineRequest;
use Modules\System\Http\Requests\UpdateMachineRequest;
use Modules\System\Http\Resources\MachineApiResource;
use Modules\System\Models\Machine;

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
}
