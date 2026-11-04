<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Enums\InstrumentCriticality;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentMovement;
use Modules\Metrology\Services\LogisticsService;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use Tests\TestCase;

class InstrumentCustodyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Station $stationLab;

    private Station $stationFloor;

    private Instrument $instrument;

    private LogisticsService $logisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Laboratório Metrológico Alfa',
            'slug' => 'lab-alfa',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->stationLab = Station::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Laboratório Metrológico Central',
            'type' => 'Laboratory',
            'location' => 'Prédio A - Sala 102',
            'is_active' => true,
        ]);

        $this->stationFloor = Station::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Célula de Usinagem CNC-01',
            'type' => 'ProductionLine',
            'location' => 'Galpão 3 - Linha 2',
            'is_active' => true,
        ]);

        $this->instrument = Instrument::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Paquímetro Digital 150 mm',
            'stock_number' => 'PAQ-042',
            'serial_number' => 'SN-PAQ-8899',
            'nfc_tag' => 'NFC-TAG-7711',
            'mpe' => 0.05,
            'acquisition_date' => now()->subYear(),
            'status' => ItemStatus::Active,
            'criticality' => InstrumentCriticality::OperationalReference,
            'current_station_id' => $this->stationLab->id,
            'location' => $this->stationLab->name,
        ]);

        $this->logisticsService = app(LogisticsService::class);
    }

    public function test_finds_instrument_by_various_codes(): void
    {
        $byTag = $this->logisticsService->findInstrumentByCode('NFC-TAG-7711');
        $this->assertNotNull($byTag);
        $this->assertSame($this->instrument->id, $byTag->id);

        $byStock = $this->logisticsService->findInstrumentByCode('PAQ-042');
        $this->assertNotNull($byStock);
        $this->assertSame($this->instrument->id, $byStock->id);

        $bySerial = $this->logisticsService->findInstrumentByCode('SN-PAQ-8899');
        $this->assertNotNull($bySerial);
        $this->assertSame($this->instrument->id, $bySerial->id);

        $nonExistent = $this->logisticsService->findInstrumentByCode('UNKNOWN-CODE-999');
        $this->assertNull($nonExistent);
    }

    public function test_records_custody_transfer_and_updates_location(): void
    {
        $movement = $this->logisticsService->recordMovement(
            instrument: $this->instrument,
            type: 'transfer',
            toStationId: $this->stationFloor->id,
            userId: $this->user->id,
            tagOrBarcode: 'PAQ-042',
            metadata: ['notes' => 'Transferência para chão de fábrica']
        );

        $this->assertInstanceOf(InstrumentMovement::class, $movement);
        $this->assertSame('transfer', $movement->type);
        $this->assertSame($this->stationLab->id, $movement->from_station_id);
        $this->assertSame($this->stationFloor->id, $movement->to_station_id);

        $this->instrument->refresh();
        $this->assertSame($this->stationFloor->id, $this->instrument->current_station_id);
        $this->assertSame($this->stationFloor->name, $this->instrument->location);
    }

    public function test_records_checkin_returning_to_warehouse(): void
    {
        // First checkout to floor
        $this->instrument->update(['current_station_id' => $this->stationFloor->id]);

        $movement = $this->logisticsService->recordMovement(
            instrument: $this->instrument,
            type: 'checkin',
            toStationId: $this->stationLab->id,
            userId: $this->user->id
        );

        $this->assertSame('checkin', $movement->type);
        $this->instrument->refresh();
        $this->assertSame($this->stationLab->id, $this->instrument->current_station_id);
    }

    public function test_retrieves_recent_movements(): void
    {
        $this->logisticsService->recordMovement(
            instrument: $this->instrument,
            type: 'checkout',
            toStationId: $this->stationFloor->id,
            userId: $this->user->id
        );

        $recent = $this->logisticsService->getRecentMovements(5);

        $this->assertGreaterThanOrEqual(1, $recent->count());
        $this->assertSame($this->instrument->id, $recent->first()->instrument_id);
    }

    public function test_logistics_scan_api_endpoint(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->postJson('/api/v1/instruments/scan', [
                'tag_id' => 'NFC-TAG-7711',
                'to_station_id' => $this->stationFloor->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('instrument.id', $this->instrument->id);

        $this->instrument->refresh();
        $this->assertSame($this->stationFloor->id, $this->instrument->current_station_id);
    }
}
