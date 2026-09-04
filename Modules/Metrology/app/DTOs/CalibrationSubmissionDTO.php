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
        return new self(
            instrumentId: (string) $data['instrument_id'],
            templateId: (string) $data['checklist_template_id'],
            date: $data['calibration_date'] ?? $data['date'] ?? now()->toDateString(),
            result: $data['result'],
            items: $data['checklist_items'] ?? $data['items'] ?? [],
            temperature: isset($data['environment']['temperature'])
                ? (float) $data['environment']['temperature']
                : (isset($data['temperature']) ? (float) $data['temperature'] : null),
            humidity: isset($data['environment']['humidity'])
                ? (float) $data['environment']['humidity']
                : (isset($data['humidity']) ? (float) $data['humidity'] : null),
            deviation: isset($data['deviation']) ? (float) $data['deviation'] : null,
            uncertainty: isset($data['uncertainty']) ? (float) $data['uncertainty'] : null,
            notes: $data['notes'] ?? null,
            performedBy: isset($data['performed_by_id']) ? (string) $data['performed_by_id'] : (auth()->id() ? (string) auth()->id() : null),
            asFoundResult: $data['as_found_result'] ?? null,
            asLeftResult: $data['as_left_result'] ?? null,
            asFoundDeviation: isset($data['as_found_deviation']) ? (float) $data['as_found_deviation'] : null,
            asLeftDeviation: isset($data['as_left_deviation']) ? (float) $data['as_left_deviation'] : null,
        );
    }
}
