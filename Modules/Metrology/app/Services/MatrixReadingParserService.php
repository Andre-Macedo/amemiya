<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

class MatrixReadingParserService
{
    /**
     * @return array{
     *     success: bool,
     *     delimiter: string,
     *     rows: array<int, array{nominal: ?float, readings: array<int, float>}>,
     *     warnings: array<int, string>
     * }
     */
    public function parse(string $rawInput, string $mode = 'sequential'): array
    {
        $cleaned = $this->cleanInput($rawInput);

        if ($cleaned === '') {
            return [
                'success' => false,
                'delimiter' => 'none',
                'rows' => [],
                'warnings' => ['Nenhum dado fornecido para importação.'],
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', $cleaned);

        if (! is_array($lines) || empty($lines)) {
            return [
                'success' => false,
                'delimiter' => 'none',
                'rows' => [],
                'warnings' => ['Não foi possível identificar linhas no texto colado.'],
            ];
        }

        $filteredLines = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));

        if (empty($filteredLines)) {
            return [
                'success' => false,
                'delimiter' => 'none',
                'rows' => [],
                'warnings' => ['O texto colado contém apenas linhas em branco.'],
            ];
        }

        $delimiter = $this->detectDelimiter($filteredLines);
        $warnings = [];
        $rows = [];

        foreach ($filteredLines as $lineIndex => $line) {
            $rawCells = explode($delimiter, $line);
            $cells = array_values(array_filter(array_map('trim', $rawCells), fn (string $cell) => $cell !== ''));

            if (empty($cells)) {
                continue;
            }

            if ($lineIndex === 0) {
                if ($this->isHeaderRow($cells)) {
                    continue;
                }
            }

            $numericCells = [];
            foreach ($cells as $cell) {
                $normalized = $this->normalizeNumericString($cell);
                if (is_numeric($normalized)) {
                    $numericCells[] = (float) $normalized;
                }
            }

            if (empty($numericCells)) {
                $warnings[] = 'Linha '.($lineIndex + 1).' ignorada por não conter valores numéricos válidos.';

                continue;
            }

            $nominal = null;
            $readings = $numericCells;

            if ($mode === 'match_nominal') {
                $nominal = array_shift($readings);
            }

            $rows[] = [
                'nominal' => $nominal,
                'readings' => array_values($readings),
            ];
        }

        return [
            'success' => ! empty($rows),
            'delimiter' => $delimiter,
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $checklistItems
     * @param  array<int, array{nominal: ?float, readings: array<int, float>}>  $parsedRows
     * @return array<int, array<string, mixed>>
     */
    public function applyToItems(array $checklistItems, array $parsedRows, string $mode = 'sequential'): array
    {
        if (empty($parsedRows)) {
            return $checklistItems;
        }

        $updatedItems = $checklistItems;

        if ($mode === 'match_nominal') {
            return $this->applyMatchedByNominal($updatedItems, $parsedRows);
        }

        return $this->applySequentially($updatedItems, $parsedRows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array{nominal: ?float, readings: array<int, float>}>  $parsedRows
     * @return array<int, array<string, mixed>>
     */
    private function applySequentially(array $items, array $parsedRows): array
    {
        $rowIndex = 0;
        $totalRows = count($parsedRows);

        foreach ($items as $index => $item) {
            if (($item['question_type'] ?? '') !== 'numeric') {
                continue;
            }

            if ($rowIndex >= $totalRows) {
                break;
            }

            $rowReadings = $parsedRows[$rowIndex]['readings'];
            $items[$index]['readings'] = $this->fillReadingsArray($item['readings'] ?? [], $rowReadings);
            $rowIndex++;
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array{nominal: ?float, readings: array<int, float>}>  $parsedRows
     * @return array<int, array<string, mixed>>
     */
    private function applyMatchedByNominal(array $items, array $parsedRows): array
    {
        foreach ($items as $index => $item) {
            if (($item['question_type'] ?? '') !== 'numeric') {
                continue;
            }

            $itemNominal = isset($item['nominal_value']) ? (float) $item['nominal_value'] : null;

            if ($itemNominal === null) {
                continue;
            }

            foreach ($parsedRows as $row) {
                if ($row['nominal'] === null) {
                    continue;
                }

                if (abs($row['nominal'] - $itemNominal) < 0.0001) {
                    $items[$index]['readings'] = $this->fillReadingsArray($item['readings'] ?? [], $row['readings']);
                    break;
                }
            }
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $existingReadings
     * @param  array<int, float>  $newValues
     * @return array<int, array<string, mixed>>
     */
    private function fillReadingsArray(array $existingReadings, array $newValues): array
    {
        if (empty($existingReadings)) {
            return array_map(fn (float $value) => ['value' => (string) $value], $newValues);
        }

        $result = $existingReadings;
        foreach ($newValues as $idx => $value) {
            if (isset($result[$idx])) {
                $result[$idx]['value'] = (string) $value;

                continue;
            }

            $result[] = ['value' => (string) $value];
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function detectDelimiter(array $lines): string
    {
        $firstFewLines = array_slice($lines, 0, 5);
        $tabCount = 0;
        $semicolonCount = 0;
        $commaCount = 0;

        foreach ($firstFewLines as $line) {
            $tabCount += substr_count($line, "\t");
            $semicolonCount += substr_count($line, ';');
            $commaCount += substr_count($line, ',');
        }

        if ($tabCount > 0) {
            return "\t";
        }

        if ($semicolonCount > 0) {
            return ';';
        }

        if ($commaCount > 0) {
            return ',';
        }

        return "\t";
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function isHeaderRow(array $cells): bool
    {
        $nonNumericCount = 0;

        foreach ($cells as $cell) {
            $normalized = $this->normalizeNumericString($cell);
            if (! is_numeric($normalized)) {
                $nonNumericCount++;
            }
        }

        return $nonNumericCount >= (int) ceil(count($cells) / 2);
    }

    private function cleanInput(string $input): string
    {
        $trimmed = trim($input);

        // Remove UTF-8 Byte Order Mark (BOM)
        if (str_starts_with($trimmed, "\xEF\xBB\xBF")) {
            $trimmed = substr($trimmed, 3);
        }

        return trim($trimmed);
    }

    private function normalizeNumericString(string $value): string
    {
        $clean = trim($value, " \t\n\r\0\x0B\"'");

        if (str_contains($clean, ',') && ! str_contains($clean, '.')) {
            $clean = str_replace(',', '.', $clean);
        }

        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            // Check if format is Brazilian/European thousand dot and decimal comma (e.g. 1.000,50)
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // US format thousand comma and decimal dot (e.g. 1,000.50)
                $clean = str_replace(',', '', $clean);
            }
        }

        return $clean;
    }
}
