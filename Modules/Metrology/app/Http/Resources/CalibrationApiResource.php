<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use function Termwind\parse;


class CalibrationApiResource extends JsonResource
{
    /**
     * Mapa de tradução dos status do banco para o frontend.
     */
    protected function getResultLabel($status)
    {
        return match ($status) {
            'approved' => 'Aprovado',
            'approved_with_restrictions' => 'Aprovado com Restrições',
            'rejected' => 'Reprovado',
            'in_calibration' => 'Em Andamento',
            default => 'Desconhecido',
        };
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,

            // Dados brutos (útil para lógica de cores no front se necessário)
            'status_key' => $this->result,

            // Dados formatados para exibição (O front espera "Aprovado"/"Reprovado")
            'result' => $this->getResultLabel($this->result),

            'calibration_date' => $this->calibration_date ? Carbon::parse($this->calibration_date)->format('d/m/Y') : 'N/A',
            'next_calibration_due' => $this->calibratedItem->calibration_due ? Carbon::parse($this->calibratedItem->calibration_due)->format('d/m/Y') : null,

            // Relacionamentos
            'checklist_id' => (string) ($this->checklist_id ?? 'N/A'),
            'performed_by' => $this->performedBy ? $this->performedBy->name : 'Sistema/Externo',

            // Detalhes técnicos
            'deviation' => $this->deviation,
            'uncertainty' => $this->uncertainty,
            'notes' => $this->notes,

            // Se tiver certificado (PDF), montar a URL completa
            'certificate_url' => $this->certificate_path ? asset('storage/' . $this->certificate_path) : null,
        ];
    }
}
