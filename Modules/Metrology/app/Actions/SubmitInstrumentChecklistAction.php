<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Metrology\DTOs\InstrumentChecklistSubmissionData;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;

class SubmitInstrumentChecklistAction
{
    /**
     * Inicializa a Action com suas dependências.
     *
     * @param  CreateChecklistAction  $createChecklistAction  Action para salvar as respostas do checklist.
     * @param  UpdateReferenceStandardKitAction  $updateKitAction  Action para atualizar kits, se aplicável.
     * @param  ProcessCalibrationAction  $processCalibrationAction  Action para avaliar o resultado final.
     */
    public function __construct(
        protected CreateChecklistAction $createChecklistAction,
        protected UpdateReferenceStandardKitAction $updateKitAction,
        protected ProcessCalibrationAction $processCalibrationAction
    ) {}

    /**
     * Processa a submissão de um checklist de calibração.
     *
     * Cria o registro de calibração, salva as respostas do checklist,
     * atualiza itens de kit relacionados e processa o resultado final (aprovação/reprovação).
     * Tudo é executado dentro de uma transação de banco de dados.
     *
     * @param  InstrumentChecklistSubmissionData  $data  DTO contendo todos os dados da submissão.
     * @return Calibration A calibração criada e processada.
     */
    public function execute(InstrumentChecklistSubmissionData $data): Calibration
    {
        return DB::transaction(function () use ($data) {
            $calibration = new Calibration;
            $calibration->calibration_date = $data->calibrationDate;
            $calibration->performed_by_id = $data->performedById;
            $calibration->type = 'internal'; // Checklist implica interna

            // Salva dados básicos da calibração
            // Polymorphic mapping é resolvido automaticamente pelo Eloquent se configurado, mas aqui estamos explicitando.
            $calibration->calibrated_item_type = Instrument::class;
            $calibration->calibrated_item_id = $data->instrumentId;

            $calibration->save();

            // 1. Cria Checklists
            // Executa a ação de criação de checklist, preenchendo as respostas e leituras enviadas.
            $this->createChecklistAction->execute($calibration, $data->checklistData);

            // 2. Atualiza Kits de Padrões (se houver)
            // Se o checklist incluiu feedback sobre padrões (ex: atualização de valor atual), processa aqui.
            if ($data->kitUpdateData) {
                $this->updateKitAction->execute($calibration, $data->kitUpdateData);
            }

            // 3. Processa Resultado (Status, Data de Vencimento)
            // Avalia se o instrumento foi aprovado ou reprovado com base na incerteza e erro (Regra de Decisão).
            // Atualiza também o status do instrumento e a próxima data de calibração.
            $this->processCalibrationAction->execute($calibration);

            return $calibration;
        });
    }
}
