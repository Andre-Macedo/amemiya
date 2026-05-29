<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\NonConformity;
use Modules\Metrology\Notifications\CalibrationRejectedNotification;
use Modules\System\Models\User;

class ProcessCalibrationAction
{
    /**
     * Processa os resultados de uma calibração e atualiza o status do instrumento.
     *
     * Avalia se os critérios de aceitação foram atendidos (Regra de Decisão)
     * e atualiza o resultado da calibração (Aprovado/Reprovado).
     * Se a calibração for publicada, também atualiza o status operacional do item calibrado.
     *
     * @param  Calibration  $calibration  A calibração a ser processada.
     */
    public function execute(Calibration $calibration): void
    {
        $item = $calibration->calibratedItem;

        if (! $item instanceof CalibratableItem) {
            return;
        }

        $this->evaluateResult($calibration, $item);
        $this->generateConformityStatement($calibration, $item);
        $this->captureProcedureSnapshot($calibration, $item);

        if ($calibration->isDirty(['result', 'conformity_statement', 'procedure_snapshot'])) {
            $calibration->saveQuietly();
        }

        // Só atualiza as propriedades do Ativo (Próximo Vencimento, Status) se a calibração estiver Publicada.
        if ($calibration->status === 'published') {
            $this->updateItemStatus($calibration, $item);
            $this->handleNonConformity($calibration, $item);
        }
    }

    private function captureProcedureSnapshot(Calibration $calibration, CalibratableItem $item): void
    {
        // Só gera se ainda não existir ou se a calibração ainda não estiver publicada
        if ($calibration->procedure_snapshot && $calibration->status === 'published') {
            return;
        }

        $template = $calibration->checklist?->template;

        $calibration->procedure_snapshot = [
            'instrument' => [
                'name' => $item->name,
                'serial_number' => $item->serial_number,
                'mpe' => $item->mpe,
                'mpe_value' => $item->getMaximumPermissibleError(),
                'resolution' => $item->resolution,
                'range' => $item->measuring_range,
                'decision_rule' => $item->getDecisionRule(),
            ],
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'version' => $template->version,
                'items' => $template->items->map(fn ($i) => [
                    'step' => $i->step,
                    'nominal_value' => $i->nominal_value,
                    'question_type' => $i->question_type,
                    'order' => $i->order,
                ])->toArray(),
            ] : null,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function generateConformityStatement(Calibration $calibration, CalibratableItem $item): void
    {
        // Só gera se ainda não existir (permite edição manual anterior se houver interface)
        if ($calibration->conformity_statement) {
            return;
        }

        $type = $item->instrumentType;
        $rule = $item->getDecisionRule();
        $result = $calibration->result;

        // Regra amigável para o certificado
        $ruleName = match ($rule) {
            'simple' => 'Simple Acceptance (ISO 14253-1)',
            'guard_band' => 'Binary Decision Rule with Guard Band (ILAC G8:09/2019)',
            'uncertainty_accounted' => 'Uncertainty Accounted (ISO 17025)',
            default => $rule,
        };

        if ($result === CalibrationResult::Approved) {
            $template = $type?->pass_statement_template ?? "O item atende aos requisitos de erro máximo permissível (MPE) estabelecidos, considerando a regra de decisão {$ruleName}.";
            $calibration->conformity_statement = $template;
        } elseif ($result === CalibrationResult::Rejected) {
            $template = $type?->fail_statement_template ?? "O item NÃO atende aos requisitos de erro máximo permissível (MPE) estabelecidos, baseando-se na regra de decisão {$ruleName}.";
            $calibration->conformity_statement = $template;
        } elseif ($result === CalibrationResult::Conditional) {
            $calibration->conformity_statement = "O item encontra-se na zona de incerteza (Zona de Dúvida). A conformidade não pode ser afirmada com o nível de confiança padrão, considerando a regra de decisão {$ruleName}.";
        }
    }

    private function evaluateResult(Calibration $calibration, CalibratableItem $item): void
    {
        $limit = $item->getMaximumPermissibleError();

        // A avaliação só ocorre se o Erro Máximo Permissível (MPE/Limit) estiver definido
        // e se a calibração possuir desvio calculado.
        if ($limit !== null && $limit > 0 && $calibration->deviation !== null) {
            $measuredError = abs((float) $calibration->deviation);
            $uncertainty = abs((float) $calibration->uncertainty);

            // Obtém a estratégia de regra de decisão configurada no item (ex: ILAC-G8 Simple Acceptance, Guard Band, etc.)
            $rule = $item->getDecisionRule();
            $strategy = $item->getDecisionRuleStrategy();

            // Avalia aprovação/reprovação pela estratégia configurada
            $passed = $strategy->evaluate($measuredError, $uncertainty, $limit);

            if ($passed) {
                // Passou na regra estrita
                if ($calibration->result !== CalibrationResult::ApprovedWithRestrictions) {
                    $calibration->result = CalibrationResult::Approved;
                }
            } else {
                // Não passou na regra estrita.
                // Se a regra for Guard Band, precisamos saber se falhou feio ( > MPE) ou se caiu na zona condicional ( > MPE - U e <= MPE)
                if ($rule === 'guard_band' && $measuredError <= $limit) {
                    $calibration->result = CalibrationResult::Conditional;
                } else {
                    $calibration->result = CalibrationResult::Rejected;
                }
            }
        }
    }

    private function updateItemStatus(Calibration $calibration, CalibratableItem $item): void
    {
        if ($calibration->result) {
            $item->processCalibrationResult($calibration, $calibration->result);
        }
    }

    private function handleNonConformity(Calibration $calibration, CalibratableItem $item): void
    {
        // Se foi reprovado, abre NC automaticamente
        if ($calibration->result === CalibrationResult::Rejected) {

            // Verifica se já existe NC para esta calibração para evitar duplicidade
            $exists = NonConformity::where('calibration_id', $calibration->id)->exists();

            if (! $exists) {
                $nc = NonConformity::create([
                    'item_type' => get_class($item),
                    'item_id' => $item->id,
                    'calibration_id' => $calibration->id,
                    'user_id' => $calibration->performed_by_id, // Quem realizou a calibração abre a NC (ou sistema)
                    'status' => 'open',
                    'priority' => 'high',
                    'title' => "Calibration Failed: {$calibration->certificate_number}",
                    'description' => 'Instrument failed calibration on '.$calibration->calibration_date->format('d/m/Y').". Deviation: {$calibration->deviation}, Uncertainty: {$calibration->uncertainty}.",
                ]);

                // Notificar administradores sobre a reprovação crítica
                $admins = User::role(['super_admin', 'admin'])->get();
                if ($admins->count() > 0) {
                    Notification::send($admins, new CalibrationRejectedNotification($calibration));
                }
            }
        }
    }
}
