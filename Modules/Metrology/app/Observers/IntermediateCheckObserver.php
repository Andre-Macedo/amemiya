<?php

declare(strict_types=1);

namespace Modules\Metrology\Observers;

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\IntermediateCheck;
use Modules\Metrology\Models\NonConformity;
use Modules\Metrology\Models\ReferenceStandard;

class IntermediateCheckObserver
{
    public function created(IntermediateCheck $check): void
    {
        $this->handleFailure($check);
    }

    public function updated(IntermediateCheck $check): void
    {
        if ($check->isDirty('result')) {
            $this->handleFailure($check);
        }
    }

    private function handleFailure(IntermediateCheck $check): void
    {
        if (! in_array(strtolower((string) $check->result), ['failed', 'fail', 'rejected'], true)) {
            return;
        }

        $instrument = $check->instrument;
        if (! $instrument instanceof Instrument) {
            return;
        }

        // 1. Bloqueia imediatamente o instrumento para prevenir uso na linha de produção
        $instrument->update([
            'status' => ItemStatus::Rejected,
        ]);

        // 2. Abre Não Conformidade automática para o instrumento
        $standard = $check->referenceStandard;
        $standardName = $standard instanceof ReferenceStandard ? $standard->name : 'Não informado';
        $formattedDate = $check->check_date ? $check->check_date->format('d/m/Y') : now()->format('d/m/Y');
        $notes = $check->notes ? " Detalhes: {$check->notes}" : '';

        NonConformity::firstOrCreate(
            [
                'tenant_id' => $check->tenant_id,
                'item_type' => Instrument::class,
                'item_id' => $instrument->id,
                'title' => "Falha em Verificação Intermediária: {$instrument->name}",
                'status' => 'open',
            ],
            [
                'user_id' => $check->performed_by,
                'priority' => 'high',
                'description' => "O instrumento reprovou na checagem intermediária realizada em {$formattedDate} utilizando o padrão '{$standardName}'.{$notes} O instrumento foi automaticamente bloqueado (status: Reprovado).",
            ]
        );
    }
}
