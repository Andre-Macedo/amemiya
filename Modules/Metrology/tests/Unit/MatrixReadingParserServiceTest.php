<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Services\MatrixReadingParserService;
use Tests\TestCase;

class MatrixReadingParserServiceTest extends TestCase
{
    private MatrixReadingParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatrixReadingParserService;
    }

    public function test_parses_tab_delimited_clipboard_data(): void
    {
        $raw = "10.01\t10.02\t10.01\r\n20.00\t20.01\t20.02\r\n30.05\t30.04\t30.06";
        $result = $this->service->parse($raw);

        $this->assertTrue($result['success']);
        $this->assertSame("\t", $result['delimiter']);
        $this->assertCount(3, $result['rows']);
        $this->assertSame([10.01, 10.02, 10.01], $result['rows'][0]['readings']);
        $this->assertSame([20.00, 20.01, 20.02], $result['rows'][1]['readings']);
        $this->assertSame([30.05, 30.04, 30.06], $result['rows'][2]['readings']);
    }

    public function test_parses_semicolon_delimited_data_with_comma_decimals(): void
    {
        $raw = "10,01; 10,02; 10,01\n20,00; 20,01; 20,02";
        $result = $this->service->parse($raw);

        $this->assertTrue($result['success']);
        $this->assertSame(';', $result['delimiter']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame([10.01, 10.02, 10.01], $result['rows'][0]['readings']);
        $this->assertSame([20.00, 20.01, 20.02], $result['rows'][1]['readings']);
    }

    public function test_ignores_header_row_automatically(): void
    {
        $raw = "R1\tR2\tR3\n10.00\t10.01\t10.02\n20.00\t20.01\t20.02";
        $result = $this->service->parse($raw);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame([10.00, 10.01, 10.02], $result['rows'][0]['readings']);
    }

    public function test_parses_nominal_matching_mode(): void
    {
        $raw = "10.0\t10.01\t10.02\n25.0\t25.00\t25.01\n50.0\t50.02\t50.03";
        $result = $this->service->parse($raw, 'match_nominal');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['rows']);
        $this->assertSame(10.0, $result['rows'][0]['nominal']);
        $this->assertSame([10.01, 10.02], $result['rows'][0]['readings']);
        $this->assertSame(25.0, $result['rows'][1]['nominal']);
        $this->assertSame([25.00, 25.01], $result['rows'][1]['readings']);
    }

    public function test_applies_readings_sequentially_to_checklist_items(): void
    {
        $items = [
            [
                'step' => 'Ponto 1',
                'question_type' => 'numeric',
                'nominal_value' => 10.0,
                'readings' => [['value' => null], ['value' => null]],
            ],
            [
                'step' => 'Inspeção Visual',
                'question_type' => 'boolean',
                'result' => 'pass',
            ],
            [
                'step' => 'Ponto 2',
                'question_type' => 'numeric',
                'nominal_value' => 20.0,
                'readings' => [['value' => null], ['value' => null]],
            ],
        ];

        $parsedRows = [
            ['nominal' => null, 'readings' => [10.05, 10.06]],
            ['nominal' => null, 'readings' => [20.01, 20.02]],
        ];

        $updated = $this->service->applyToItems($items, $parsedRows, 'sequential');

        $this->assertSame('10.05', $updated[0]['readings'][0]['value']);
        $this->assertSame('10.06', $updated[0]['readings'][1]['value']);
        $this->assertSame('pass', $updated[1]['result']);
        $this->assertSame('20.01', $updated[2]['readings'][0]['value']);
        $this->assertSame('20.02', $updated[2]['readings'][1]['value']);
    }

    public function test_applies_readings_by_matching_nominal_values(): void
    {
        $items = [
            [
                'step' => 'Ponto 50',
                'question_type' => 'numeric',
                'nominal_value' => 50.0,
                'readings' => [['value' => null]],
            ],
            [
                'step' => 'Ponto 10',
                'question_type' => 'numeric',
                'nominal_value' => 10.0,
                'readings' => [['value' => null]],
            ],
        ];

        $parsedRows = [
            ['nominal' => 10.0, 'readings' => [10.02]],
            ['nominal' => 50.0, 'readings' => [50.08]],
        ];

        $updated = $this->service->applyToItems($items, $parsedRows, 'match_nominal');

        $this->assertSame('50.08', $updated[0]['readings'][0]['value']);
        $this->assertSame('10.02', $updated[1]['readings'][0]['value']);
    }

    public function test_handles_empty_or_whitespace_input(): void
    {
        $result = $this->service->parse("   \n\r\n   ");
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['rows']);
    }
}
