<?php

namespace Modules\Metrology\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentMovement;

class LogisticsService
{
    /**
     * Processa uma leitura de tag (NFC/RFID) e atualiza o estado físico do instrumento.
     */
    public function processScan(string $tagId, ?string $toStationId = null, array $metadata = []): Instrument
    {
        $instrument = Instrument::where('nfc_tag', $tagId)->firstOrFail();
        $fromStationId = $instrument->current_station_id;

        // Se não informar destino, assumimos uma transferência para a estação logada ou padrão
        $toStationId = $toStationId ?? Auth::user()?->station_id;

        // Determina o tipo de movimento
        $type = $this->determineMovementType($fromStationId, $toStationId);

        // 1. Registra a movimentação
        InstrumentMovement::create([
            'tenant_id' => $instrument->tenant_id,
            'instrument_id' => $instrument->id,
            'tag_id' => $tagId,
            'type' => $type,
            'from_station_id' => $fromStationId,
            'to_station_id' => $toStationId,
            'user_id' => Auth::id(),
            'metadata' => $metadata,
        ]);

        // 2. Atualiza a localização real do instrumento
        if ($toStationId) {
            $instrument->update([
                'current_station_id' => $toStationId,
            ]);
        }

        return $instrument;
    }

    protected function determineMovementType(?string $from, ?string $to): string
    {
        if (! $from && $to) {
            return 'checkin';
        }
        if ($from && ! $to) {
            return 'checkout';
        }

        // Se as estações forem de tipos diferentes (ex: Lab -> Floor), podemos ser mais específicos
        return 'transfer';
    }
}
