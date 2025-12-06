<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Metrology\Http\Resources\CalibrationApiResource;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\ChecklistTemplateItem;
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
        // 1. Instrumentos (Mantido)
        $instruments = Instrument::query()
            ->select('id', 'name', 'serial_number', 'instrument_type_id', 'status')
            ->get();

        // 2. KITS (Pais com Filhos)
        // Precisamos carregar o 'parent' nos filhos para o cálculo do effective funcionar?
        // Não, porque aqui estamos carregando o PAI ($kits) e os filhos dele.
        // O filho já tem acesso ao pai via relação inversa se o Model estiver correto,
        // mas no eager loading do 'with', o objeto filho vem "limpo".

        $kits = ReferenceStandard::query()
            ->whereNull('parent_id')
            ->whereHas('children')
            ->with(['children' => function ($query) {
                // Carregamos 'parent_id' para o Laravel saber vincular
                $query->select('id', 'parent_id', 'name', 'serial_number', 'stock_number', 'nominal_value', 'actual_value', 'uncertainty');
            }])
            // Selecionamos campos necessários do Pai para passar para os filhos via PHP se necessário,
            // ou confiamos no $appends se carregarmos 'children.parent'.
            // Para performance, vamos carregar children.parent
            ->with('children.parent')
            ->select('id', 'name', 'serial_number', 'stock_number')
            ->get()
            // Transformamos para garantir que o append funcione
            ->map(function ($kit) {
                // Força o append nos filhos
                $kit->children->each->setAppends(['effective_serial_number', 'effective_stock_number']);
                return $kit;
            });

        // 3. Padrões Individuais (Soltos)
        $allStandards = ReferenceStandard::query()
            ->where('status', 'active')
            // Importante: Carregar 'parent' se existirem itens filhos soltos nessa lista
            ->with('parent')
            ->select('id', 'name', 'serial_number', 'stock_number', 'nominal_value', 'parent_id')
            ->orderBy('nominal_value')
            ->get()
            ->each->setAppends(['effective_serial_number', 'effective_stock_number']); // Força o append

        $allStandardsCount = $allStandards->count();

        return response()->json([
            'instruments' => $instruments,
            'kits' => $kits,
            'standards' => $allStandards,
            'count' => $allStandardsCount
        ]);
    }


    /**
     * Salva a calibração completa
     */
    public function store(Request $request)
    {
        // Log para debug (ver o que está chegando)
        Log::info('Payload Recebido:', $request->all());

        // Validação
        $validated = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'checklist_template_id' => 'required|exists:checklist_templates,id',
            'calibration_date' => 'date',
            'result' => 'required',
            'items' => 'nullable|array', // Agora pode vir incompleto
            'environment.temperature' => 'nullable',
            'environment.humidity' => 'nullable',
            'deviation' => 'nullable',
            'uncertainty' => 'nullable',
            'notes' => 'nullable',
        ]);

        DB::beginTransaction();

        try {
            // 1. Cria Cabeçalho da Calibração
            $calibration = Calibration::create([
                'calibrated_item_type' => Instrument::class,
                'calibrated_item_id' => $validated['instrument_id'],
                'checklist_id' => null,
                'calibration_date' => $validated['calibration_date'] ?? now(),
                'type' => 'internal',
                'result' => $validated['result'],
                'temperature' => $request->input('environment.temperature'),
                'humidity' => $request->input('environment.humidity'),
                'deviation' => $validated['deviation'] ?? null,
                'uncertainty' => $validated['uncertainty'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'performed_by_id' => auth()->id() ?? 1,
            ]);

            // 2. Cria Checklist
            $checklist = Checklist::create([
                'calibration_id' => $calibration->id,
                'checklist_template_id' => $validated['checklist_template_id'],
                'completed' => true,
            ]);

            // 3. Processamento Inteligente dos Itens
            // Em vez de confiar apenas no que o App mandou, buscamos o Template Original.
            // Isso garante que todos os passos sejam salvos, mesmo os vazios.

            $templateItems = ChecklistTemplateItem::where('checklist_template_id', $validated['checklist_template_id'])
                ->orderBy('order')
                ->get();

            // Transforma o array do App em uma Collection chaveada pelo item_id para busca rápida
            // Ex: ['31' => ['readings' => ..., 'reference_standard_id' => ...]]
            $appItemsMap = collect($request->input('items', []))->keyBy('item_id');

            $checklistItemsData = [];

            foreach ($templateItems as $templateItem) {
                // Busca o input correspondente enviado pelo App
                $appResponse = $appItemsMap->get($templateItem->id);

                // --- PROCESSAMENTO DINÂMICO ---
                $readingsJson = null;
                $resultItem = null;
                $notesItem = null;
                $standardId = null;
                $isCompleted = false;

                if ($appResponse) {
                    // TIPO NUMÉRICO -> Salva readings e standard
                    if ($templateItem->question_type === 'numeric') {
                        if (!empty($appResponse['readings'])) {
                            $rawReadings = is_array($appResponse['readings']) ? $appResponse['readings'] : [$appResponse['readings']];
                            $readingsJson = json_encode($rawReadings);
                            $isCompleted = true;
                        }
                        // Validação do ID do padrão (evita erro de string)
                        if (isset($appResponse['reference_standard_id']) && is_numeric($appResponse['reference_standard_id'])) {
                            $standardId = $appResponse['reference_standard_id'];
                        }
                    }
                    // TIPO BOOLEANO -> Salva result
                    elseif ($templateItem->question_type === 'boolean') {
                        $resultItem = $appResponse['result'] ?? null;
                        if ($resultItem) $isCompleted = true;
                    }
                    // TIPO TEXTO -> Salva notes
                    elseif ($templateItem->question_type === 'text') {
                        $notesItem = $appResponse['notes'] ?? null;
                        if ($notesItem) $isCompleted = true;
                    }
                }

                $checklistItemsData[] = [
                    'checklist_id' => $checklist->id,
                    'step' => $templateItem->step,
                    'question_type' => $templateItem->question_type,
                    'order' => $templateItem->order,
                    'required_readings' => $templateItem->required_readings,
                    'completed' => $isCompleted,

                    // Campos mapeados corretamente agora:
                    'readings' => $readingsJson,
                    'result' => $resultItem,
                    'notes' => $notesItem,
                    'reference_standard_id' => $standardId,

                    'uncertainty' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ChecklistItem::insert($checklistItemsData);

            $calibration->update(['checklist_id' => $checklist->id]);

            // Força execução dos eventos de modelo (atualizar vencimento)
            $calibration->touch();

            DB::commit();

            return response()->json([
                'message' => 'Salvo com sucesso!',
                'id' => $calibration->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log detalhado do erro real
            Log::error('Erro API Calibration: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error' => 'Erro ao processar calibração.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
