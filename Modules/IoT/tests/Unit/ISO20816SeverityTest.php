<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Unit;

use Modules\IoT\Services\ISO20816SeverityService;
use Tests\TestCase;

class ISO20816SeverityTest extends TestCase
{
    private ISO20816SeverityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ISO20816SeverityService;
    }

    public function test_it_converts_acceleration_in_g_to_velocity_rms_in_mms(): void
    {
        // Em 1750 RPM, a conversão física de 0.05g deve resultar em ~2.676 mm/s
        $velocity = $this->service->calculateVelocityRms(0.05, 1750);
        $this->assertNotNull($velocity);
        $this->assertEqualsWithDelta(2.676, $velocity, 0.05);

        // Se passar explicitVelocityRms, deve prevalecer
        $explicit = $this->service->calculateVelocityRms(0.05, 1750, 3.14);
        $this->assertEquals(3.14, $explicit);
    }

    public function test_it_correctly_classifies_iso_20816_zones_for_group_2_rigid(): void
    {
        // Zona A: <= 1.4 mm/s (Excelente)
        $evalA = $this->service->evaluate(1.2);
        $this->assertEquals('A', $evalA['zone']);
        $this->assertEquals('excellent', $evalA['status']);
        $this->assertFalse($evalA['action_required']);

        // Zona B: 1.4 < v <= 2.8 mm/s (Aceitável)
        $evalB = $this->service->evaluate(2.1);
        $this->assertEquals('B', $evalB['zone']);
        $this->assertEquals('acceptable', $evalB['status']);
        $this->assertFalse($evalB['action_required']);

        // Zona C: 2.8 < v <= 4.5 mm/s (Alerta / Manutenção Programada)
        $evalC = $this->service->evaluate(3.6);
        $this->assertEquals('C', $evalC['zone']);
        $this->assertEquals('warning', $evalC['status']);
        $this->assertTrue($evalC['action_required']);

        // Zona D: > 4.5 mm/s (Perigo / Parada Imediata)
        $evalD = $this->service->evaluate(5.8);
        $this->assertEquals('D', $evalD['zone']);
        $this->assertEquals('critical', $evalD['status']);
        $this->assertTrue($evalD['action_required']);
    }

    public function test_it_handles_null_or_invalid_inputs_gracefully(): void
    {
        $evalNull = $this->service->evaluate(null);
        $this->assertNull($evalNull['zone']);
        $this->assertEquals('unknown', $evalNull['status']);

        $evalZero = $this->service->evaluate(0.0);
        $this->assertNull($evalZero['zone']);
    }
}
