<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentMovement;
use Modules\Metrology\Services\LogisticsService;
use Modules\System\Models\Station;

class InstrumentCustodyPage extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $title = 'Custódia & Movimentação Metrológica';

    protected static ?string $navigationLabel = 'Custódia & Movimentação';

    protected static ?int $navigationSort = 45;

    protected static string $routePath = '/custody-scanner';

    protected string $view = 'metrology::filament.pages.instrument-custody';

    public string $search_code = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $scannedInstrument = null;

    public string $movement_type = 'checkout';

    public ?string $to_station_id = null;

    public string $notes = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $lastMovementResult = null;

    public function mount(): void
    {
        if (request()->has('code')) {
            $this->search_code = (string) request()->query('code');
            $this->lookupInstrument();
        }
    }

    public function lookupInstrument(?string $code = null): void
    {
        $targetCode = trim($code ?? $this->search_code);

        if ($targetCode === '') {
            return;
        }

        $service = app(LogisticsService::class);
        $instrument = $service->findInstrumentByCode($targetCode);

        if (! $instrument) {
            $this->scannedInstrument = null;
            Notification::make()
                ->title('Instrumento Não Encontrado')
                ->body("Nenhum instrumento localizado para o código digitado/lido: {$targetCode}")
                ->danger()
                ->send();

            return;
        }

        $isOverdue = $instrument->calibration_due ? $instrument->calibration_due->isPast() : false;

        $this->scannedInstrument = [
            'id' => $instrument->id,
            'name' => $instrument->name,
            'stock_number' => $instrument->stock_number,
            'serial_number' => $instrument->serial_number,
            'asset_number' => $instrument->asset_number,
            'nfc_tag' => $instrument->nfc_tag,
            'instrument_type' => $instrument->instrumentType?->name ?? 'Geral',
            'status' => $instrument->status?->value ?? 'active',
            'location' => $instrument->location ?? 'Almoxarifado Metrológico',
            'current_station_id' => $instrument->current_station_id,
            'current_station_name' => $instrument->station?->name ?? $instrument->location ?? 'Almoxarifado Central',
            'calibration_due' => $instrument->calibration_due?->format('d/m/Y') ?? 'N/D',
            'is_overdue' => $isOverdue,
            'criticality' => $instrument->criticality?->value ?? 'operational_reference',
            'is_critical' => $instrument->isCritical(),
        ];

        // Sugere o tipo de movimentação de acordo com o estado atual
        if ($instrument->current_station_id) {
            $this->movement_type = 'checkin';
            $this->to_station_id = null;
        } else {
            $this->movement_type = 'checkout';
        }

        Notification::make()
            ->title('Instrumento Identificado')
            ->body("{$instrument->stock_number} - {$instrument->name}")
            ->success()
            ->send();
    }

    public function confirmMovement(): void
    {
        if (! $this->scannedInstrument) {
            Notification::make()
                ->title('Nenhum Instrumento Selecionado')
                ->body('Efetue a leitura de um instrumento antes de registrar a movimentação.')
                ->warning()
                ->send();

            return;
        }

        if (in_array($this->movement_type, ['checkout', 'transfer'], true) && ! $this->to_station_id) {
            Notification::make()
                ->title('Selecione o Posto de Destino')
                ->body('Para saídas ou transferências, selecione a estação ou bancada de destino.')
                ->warning()
                ->send();

            return;
        }

        $instrument = Instrument::find($this->scannedInstrument['id']);

        if (! $instrument) {
            Notification::make()
                ->title('Erro ao carregar instrumento')
                ->danger()
                ->send();

            return;
        }

        $service = app(LogisticsService::class);
        $movement = $service->recordMovement(
            instrument: $instrument,
            type: $this->movement_type,
            toStationId: $this->to_station_id,
            userId: auth()->id(),
            tagOrBarcode: $this->search_code ?: $instrument->stock_number,
            metadata: [
                'notes' => $this->notes,
                'source' => 'filament_custody_scanner',
            ]
        );

        $destStation = $this->to_station_id ? Station::find($this->to_station_id) : null;
        $destName = $destStation?->name ?? ($this->movement_type === 'checkin' ? 'Almoxarifado Metrológico' : 'Em Trânsito');

        $this->lastMovementResult = [
            'id' => $movement->id,
            'type' => $this->movement_type,
            'instrument_name' => $instrument->name,
            'stock_number' => $instrument->stock_number,
            'destination' => $destName,
            'timestamp' => now()->format('d/m/Y H:i:s'),
        ];

        // Atualiza o card do instrumento
        $this->lookupInstrument($instrument->id);

        $this->search_code = '';
        $this->notes = '';

        Notification::make()
            ->title('Movimentação Registrada')
            ->body("Instrumento {$instrument->stock_number} movimentado com sucesso.")
            ->success()
            ->send();
    }

    public function clearInstrument(): void
    {
        $this->scannedInstrument = null;
        $this->search_code = '';
        $this->notes = '';
    }

    /**
     * @return Collection<int, Station>
     */
    public function getStationsProperty(): Collection
    {
        return Station::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, InstrumentMovement>
     */
    public function getRecentMovementsProperty(): Collection
    {
        $service = app(LogisticsService::class);

        return $service->getRecentMovements(10);
    }
}
