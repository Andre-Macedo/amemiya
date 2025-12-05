<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Metrology\Http\Resources\CalibrationApiResource;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class CalibrationApiController extends Controller
{
    /**
     * Retorna os detalhes de uma calibração específica.
     *
     * @param mixed $id
     * @return CalibrationApiResource
     */
    public function show($id)
    {
        $calibration = Calibration::with(['performedBy', 'calibratedItem'])->findOrFail($id);

        return new CalibrationApiResource($calibration);
    }


    /**
     * Retorna dados auxiliares para a tela de calibração (Kits e Instrumentos)
     */
    public function options()
    {
        // 1. Busca Instrumentos "Em Calibração" ou que precisam calibrar
        $instruments = Instrument::query()
            // Se quiser filtrar por status: ->where('status', 'in_calibration')
            ->select('id', 'name', 'serial_number', 'instrument_type_id')
            ->get();

        // 2. Busca Kits de Padrão (Pais que têm filhos)
        // Carrega os filhos para o App fazer o "Auto-Match" localmente
        $kits = ReferenceStandard::query()
            ->whereNull('parent_id') // Apenas pais
            ->whereHas('children')   // Que têm filhos (Kits)
            ->with(['children' => function ($query) {
                $query->select('id', 'parent_id', 'name', 'nominal_value', 'actual_value', 'uncertainty');
            }])
            ->select('id', 'name', 'serial_number')
            ->get();

        return response()->json([
            'instruments' => $instruments,
            'kits' => $kits,
        ]);
    }

    /**
     * Salva a calibração completa
     */
    public function store(Request $request)
    {
        // Validação básica
        $validated = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'checklist_template_id' => 'required|exists:checklist_templates,id',
            'calibration_date' => 'date',
            'result' => 'required|in:approved,rejected,approved_with_restrictions',
            'items' => 'required|array', // Itens do checklist
            'items.*.item_id' => 'required', // ID do item do template (não salvamos, mas usamos pra ordem)
            'items.*.readings' => 'nullable', // Array de leituras ou string
            'items.*.reference_standard_id' => 'nullable|exists:reference_standards,id',
            'environment.temperature' => 'nullable|numeric',
            'environment.humidity' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'uncertainty' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1. Cria a Calibração (Cabeçalho)
            $calibration = Calibration::create([
                'calibrated_item_type' => Instrument::class,
                'calibrated_item_id' => $validated['instrument_id'],
                'checklist_id' => null, // Atualiza depois
                'calibration_date' => $validated['calibration_date'] ?? now(),
                'type' => 'internal', // Padrão do App
                'result' => $validated['result'],
                'temperature' => $validated['environment']['temperature'] ?? null,
                'humidity' => $validated['environment']['humidity'] ?? null,
                'deviation' => $validated['deviation'] ?? null,
                'uncertainty' => $validated['uncertainty'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'performed_by_id' => auth()->id() ?? 1, // Usuário logado
            ]);

            // 2. Cria o Checklist
            $checklist = Checklist::create([
                'calibration_id' => $calibration->id,
                'checklist_template_id' => $validated['checklist_template_id'],
                'completed' => true, // Se veio do App, assumimos que terminou
            ]);

            // 3. Processa os Itens (Replica a lógica do Filament)
            $checklistItemsData = [];

            // Precisamos buscar os dados originais do template para pegar 'step', 'order', etc.
            // O App manda apenas o ID e as leituras.
            $templateItems = \Modules\Metrology\Models\ChecklistTemplateItem::where('checklist_template_id', $validated['checklist_template_id'])->get()->keyBy('id');

            foreach ($validated['items'] as $appItem) {
                // O 'item_id' que vem do app é o ID do item do TEMPLATE
                $templateItem = $templateItems->get($appItem['item_id']);

                if (!$templateItem) continue;

                // Formata leituras para JSON
                $readingsJson = null;
                if (!empty($appItem['readings'])) {
                    // Se for array de objetos {value: x}, extrai. Se for array simples, usa.
                    $rawReadings = is_array($appItem['readings']) ? $appItem['readings'] : [$appItem['readings']];
                    // Normaliza para array de strings/numbers
                    $readingsJson = json_encode($rawReadings);
                }

                $checklistItemsData[] = [
                    'checklist_id' => $checklist->id,
                    'step' => $templateItem->step,
                    'question_type' => $templateItem->question_type,
                    'order' => $templateItem->order,
                    'required_readings' => $templateItem->required_readings,
                    'completed' => true,
                    'readings' => $readingsJson,
                    'result' => null, // O App não manda aprovado/reprovado por linha no numeric, só no global. (A menos que seja boolean)
                    'notes' => null,
                    'reference_standard_id' => $appItem['reference_standard_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ChecklistItem::insert($checklistItemsData);

            // 4. Vincula Checklist na Calibração
            $calibration->update(['checklist_id' => $checklist->id]);

            // 5. Aciona a Lógica de Negócio (Booted do Model)
            // Ao salvar/atualizar, o Model Calibration roda o evento 'saved' que:
            // - Calcula vencimento
            // - Atualiza status do instrumento
            // Como criamos com create(), o 'saved' já rodou, mas talvez falte dados.
            // Vamos dar um "touch" final para garantir.
            $calibration->touch();

            DB::commit();

            return response()->json([
                'message' => 'Calibração salva com sucesso!',
                'calibration_id' => $calibration->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar calibração via API: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao salvar.', 'details' => $e->getMessage()], 500);
        }
    }
}
