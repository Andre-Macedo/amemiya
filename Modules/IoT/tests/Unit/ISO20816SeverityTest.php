<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Unit;

use Modules\IoT\Services\ISO20816SeverityService;
use Modules\IoT\Services\PrescriptiveDiagnosticsService;
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

    public function test_it_classifies_small_machine_below_15kw_and_formats_standard_label(): void
    {
        $evalA = $this->service->evaluate(0.50, 'small_machine', 'rigid');
        $this->assertSame('A', $evalA['zone']);
        $this->assertStringContainsString('ISO 10816-1 Classe I', $evalA['standard']);
        $this->assertStringContainsString('Base Rígida', $evalA['standard']);

        $evalB = $this->service->evaluate(1.20, 'small_machine', 'rigid');
        $this->assertSame('B', $evalB['zone']);

        $evalGroup1 = $this->service->evaluate(6.50, 'group_1', 'flexible');
        $this->assertSame('B', $evalGroup1['zone']);
        $this->assertStringContainsString('Grupo 1', $evalGroup1['standard']);
        $this->assertStringContainsString('Base Flexível', $evalGroup1['standard']);
    }

    public function test_it_supports_valid_custom_thresholds_and_ignores_invalid_ones(): void
    {
        $custom = $this->service->evaluate(0.9, 'small_machine', 'rigid', [
            'zone_a_max' => 1.0,
            'zone_b_max' => 2.5,
            'zone_c_max' => 4.0,
        ]);
        $this->assertSame('A', $custom['zone']);
        $this->assertStringContainsString('Limiar Customizado', $custom['standard']);

        $invalid = $this->service->evaluate(0.9, 'small_machine', 'rigid', [
            'zone_a_max' => 3.0,
            'zone_b_max' => 2.0,
            'zone_c_max' => 4.0,
        ]);
        $this->assertSame('B', $invalid['zone']);
    }

    public function test_it_preserves_velocity_in_mms_when_iso_is_not_applicable_for_fractional_machines(): void
    {
        $eval = $this->service->evaluate(1.35, 'baseline_only', 'rigid');

        $this->assertNull($eval['zone']);
        $this->assertSame(1.35, $eval['velocity_rms']);
        $this->assertFalse($eval['iso_applicable']);
        $this->assertSame('not_applicable', $eval['status']);
        $this->assertFalse($eval['action_required']);
        $this->assertStringContainsString('mm/s', $eval['standard']);

        $diagnostics = new PrescriptiveDiagnosticsService($this->service);
        $prescriptive = $diagnostics->generateDiagnosis($eval, 'saudavel', 0.94, 42.0);

        $this->assertSame('Operação Mecânica Estável', $prescriptive['root_cause']);
        $this->assertFalse($prescriptive['iso_applicable']);
        $this->assertStringContainsString('1.35 mm/s', $prescriptive['summary_text']);
    }
}
