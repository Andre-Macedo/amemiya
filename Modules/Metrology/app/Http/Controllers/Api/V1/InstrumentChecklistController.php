<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Metrology\Actions\SubmitInstrumentChecklistAction;
use Modules\Metrology\DTOs\InstrumentChecklistSubmissionData;
use Modules\Metrology\Http\Resources\ChecklistTemplateApiResource;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;

class InstrumentChecklistController extends Controller
{
    // 1. Lista todos os procedimentos disponíveis para o tipo deste instrumento
    public function index(Instrument $instrument)
    {
        // Busca templates que batem com o tipo do instrumento
        $templates = ChecklistTemplate::where('instrument_type_id', $instrument->instrument_type_id)
            ->select('id', 'name') // Só precisamos do nome para a lista
            ->get();

        return response()->json(['data' => $templates]);
    }

    // 2. Retorna os itens (perguntas) de um template específico
    public function show(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->load('items');

        return new ChecklistTemplateApiResource($checklistTemplate);
    }

    // 3. Submete o checklist preenchido (Salva Calibração + Checklist + Status)
    public function store(
        Request $request,
        SubmitInstrumentChecklistAction $action
    ) {
        // Validação básica se necessário, ou confiar na Action/DTO?
        // Idealmente FormRequest, mas por brevidade faremos aqui ou confiaremos no DTO safe cast.
        // Vamos usar DTO safe cast por enquanto conforme pedido "Controller burro".

        $data = InstrumentChecklistSubmissionData::fromArray($request->all());

        $calibration = $action->execute($data);

        return response()->json([
            'message' => 'Instrument checklist submitted successfully',
            'calibration_id' => $calibration->id,
            'result' => $calibration->result,
        ], 201);
    }
}
