<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentMovement;
use Modules\System\Models\Station;

class LogisticsService
{
    /**
     * Localiza um instrumento por qualquer código identificador (Tag NFC, Patrimônio, Série ou ID).
     */
    public function findInstrumentByCode(string $code): ?Instrument
    {
        $cleanCode = trim($code);

        if ($cleanCode === '') {
            return null;
        }

        return Instrument::query()
            ->with(['station', 'instrumentType'])
            ->where(function ($query) use ($cleanCode): void {
                $query->where('nfc_tag', $cleanCode)
                    ->orWhere('stock_number', $cleanCode)
                    ->orWhere('serial_number', $cleanCode)
                    ->orWhere('asset_number', $cleanCode)
                    ->orWhere('id', $cleanCode);
            })
            ->first();
    }

    /**
     * Processa uma movimentação explícita de custódia metrológica.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordMovement(
        Instrument $instrument,
        string $type,
        ?string $toStationId = null,
        ?string $userId = null,
        ?string $tagOrBarcode = null,
        array $metadata = []
    ): InstrumentMovement {
        $fromStationId = $instrument->current_station_id;
        $effectiveUserId = $userId ?? Auth::id();

        $destinationStation = $toStationId ? Station::find($toStationId) : null;

        $movement = InstrumentMovement::create([
            'tenant_id' => $instrument->tenant_id,
            'instrument_id' => $instrument->id,
            'tag_id' => $tagOrBarcode ?? $instrument->nfc_tag ?? $instrument->stock_number,
            'type' => $type,
            'from_station_id' => $fromStationId,
            'to_station_id' => $toStationId,
            'user_id' => $effectiveUserId,
            'metadata' => array_merge($metadata, [
                'recorded_at' => now()->toIso8601String(),
                'instrument_name' => $instrument->name,
                'stock_number' => $instrument->stock_number,
            ]),
        ]);

        if ($toStationId) {
            $instrument->update([
                'current_station_id' => $toStationId,
                'location' => $destinationStation?->name ?? $instrument->location,
            ]);
        } elseif ($type === 'checkout') {
            $instrument->update([
                'location' => 'Em trânsito / Em campo',
            ]);
        }

        return $movement;
    }

    /**
     * Processa uma leitura rápida de tag (NFC/RFID/Scanner) e atualiza o estado físico.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function processScan(string $tagId, ?string $toStationId = null, array $metadata = []): Instrument
    {
        $instrument = $this->findInstrumentByCode($tagId);

        if (! $instrument) {
            throw new ModelNotFoundException("Instrumento não encontrado com a identificação: {$tagId}");
        }

        $fromStationId = $instrument->current_station_id;
        $effectiveToStationId = $toStationId ?? Auth::user()?->station_id;
        $type = $this->determineMovementType($fromStationId, $effectiveToStationId);

        $this->recordMovement(
            instrument: $instrument,
            type: $type,
            toStationId: $effectiveToStationId,
            userId: Auth::id(),
            tagOrBarcode: $tagId,
            metadata: $metadata
        );

        return $instrument->fresh(['station']);
    }

    /**
     * Retorna o histórico recente de movimentações do laboratório.
     *
     * @return Collection<int, InstrumentMovement>
     */
    public function getRecentMovements(int $limit = 15): Collection
    {
        return InstrumentMovement::query()
            ->with(['instrument', 'fromStation', 'toStation', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function determineMovementType(?string $from, ?string $to): string
    {
        if (! $from && $to) {
            return 'checkin';
        }

        if ($from && ! $to) {
            return 'checkout';
        }

        return 'transfer';
    }
}
