<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

/**
 * Data Transfer Object for calibration submission.
 *
 * Encapsulates all data required to record a new calibration and its checklist items.
 */
class CalibrationSubmissionDTO
{
    /**
     * Initializes the DTO.
     *
     * Args:
     *     instrumentId: ID of the instrument being calibrated.
     *     templateId: ID of the checklist template used.
     *     date: Date of the calibration.
     *     result: Overall technical result (pass/fail/etc).
     *     items: Array of checklist measurements and boolean results.
     *     temperature: Ambient temperature during calibration.
     *     humidity: Ambient humidity during calibration.
     *     deviation: Maximum measured deviation.
     *     uncertainty: Calculated expanded uncertainty.
     *     notes: General observations.
     *     performedBy: User ID of the technician.
     */
    public function __construct(
        public readonly string $instrumentId,
        public readonly string $templateId,
        public readonly string $date,
        public readonly string $result,
        public readonly array $items = [],
        public readonly ?float $temperature = null,
        public readonly ?float $humidity = null,
        public readonly ?float $deviation = null,
        public readonly ?float $uncertainty = null,
        public readonly ?string $notes = null,
        public readonly ?string $performedBy = null,
        public readonly ?string $asFoundResult = null,
        public readonly ?string $asLeftResult = null,
        public readonly ?float $asFoundDeviation = null,
        public readonly ?float $asLeftDeviation = null,
    ) {}

    /**
     * Creates a DTO from a request or validated array.
     *
     * Args:
     *     data: The source data array.
     *
     * Returns:
     *     A new instance of CalibrationSubmissionDTO.
     */
    public static function fromArray(array $data): self
    {
        $rawResult = $data['result'] ?? null;
        $asFoundResult = $data['as_found_result'] ?? $rawResult;
        $asLeftResult = $data['as_left_result'] ?? null;

        $deviation = isset($data['deviation']) ? (float) $data['deviation'] : null;
        $asFoundDeviation = isset($data['as_found_deviation']) ? (float) $data['as_found_deviation'] : $deviation;
        $asLeftDeviation = isset($data['as_left_deviation']) ? (float) $data['as_left_deviation'] : null;

        // Se As Found aprovou e As Left não foi preenchido, herda as_left = as_found
        if (in_array(strtolower((string) $asFoundResult), ['approved', 'pass']) && $asLeftResult === null) {
            $asLeftResult = $asFoundResult;
            $asLeftDeviation = $asLeftDeviation ?? $asFoundDeviation;
        }

        // Se As Found reprovou mas foi feito ajuste e As Left aprovou: resultado final é Aprovado com Restrições (após ajuste)
        $resolvedResult = $rawResult;
        if (in_array(strtolower((string) $asFoundResult), ['rejected', 'fail']) && in_array(strtolower((string) $asLeftResult), ['approved', 'pass'])) {
            $resolvedResult = 'approved_with_restrictions';
        }

        return new self(
            instrumentId: (string) $data['instrument_id'],
            templateId: (string) $data['checklist_template_id'],
            date: $data['calibration_date'] ?? $data['date'] ?? now()->toDateString(),
            result: $resolvedResult ?? $asFoundResult ?? 'approved',
            items: $data['checklist_items'] ?? $data['items'] ?? [],
            temperature: isset($data['environment']['temperature'])
                ? (float) $data['environment']['temperature']
                : (isset($data['temperature']) ? (float) $data['temperature'] : null),
            humidity: isset($data['environment']['humidity'])
                ? (float) $data['environment']['humidity']
                : (isset($data['humidity']) ? (float) $data['humidity'] : null),
            deviation: $asLeftDeviation ?? $asFoundDeviation ?? $deviation,
            uncertainty: isset($data['uncertainty']) ? (float) $data['uncertainty'] : null,
            notes: $data['notes'] ?? null,
            performedBy: isset($data['performed_by_id']) ? (string) $data['performed_by_id'] : (auth()->id() ? (string) auth()->id() : null),
            asFoundResult: $asFoundResult,
            asLeftResult: $asLeftResult,
            asFoundDeviation: $asFoundDeviation,
            asLeftDeviation: $asLeftDeviation,
        );
    }
}
