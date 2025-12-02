<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Http\Resources\ChecklistTemplateApiResource;

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
}
