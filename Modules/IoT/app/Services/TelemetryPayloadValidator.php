<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

use Illuminate\Support\Carbon;

class TelemetryPayloadValidator
{
    /**
     * Valida integridade física e estrutural do payload de telemetria antes de persistir ou classificar.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>,
     *     sanitized: array{
     *         temp_celsius: ?float,
     *         battery_voltage: ?float
     *     }
     * }
     */
    public function validate(array $payload): array
    {
        $errors = [];
        $warnings = [];

        $deviceId = $payload['device_id'] ?? null;
        if (! is_string($deviceId) || trim($deviceId) === '') {
            $errors[] = 'device_id ausente ou inválido';
        }

        $nodeId = $payload['node_id'] ?? null;
        if (! is_string($nodeId) || trim($nodeId) === '') {
            $errors[] = 'node_id ausente ou inválido';
        }

        $this->validateNonNegativeFinite($payload['rms_global'] ?? null, 'rms_global', $errors);
        $this->validateNonNegativeFinite($payload['velocity_rms'] ?? null, 'velocity_rms', $errors);

        if (is_array($payload['features'] ?? null)) {
            $this->validateNonNegativeFinite($payload['features']['v_rms'] ?? null, 'features.v_rms', $errors);
            $this->validateNonNegativeFinite($payload['features']['rpm_estimated'] ?? null, 'features.rpm_estimated', $errors, 50000.0);
        }

        $this->validateNonNegativeFinite($payload['rpm'] ?? null, 'rpm', $errors, 50000.0);
        $this->validateNonNegativeFinite($payload['rpm_estimated'] ?? null, 'rpm_estimated', $errors, 50000.0);

        $confidence = $payload['confianca'] ?? $payload['ml_confidence'] ?? null;
        if ($confidence !== null) {
            if (! $this->isFiniteNumber($confidence) || (float) $confidence < 0.0 || (float) $confidence > 1.0) {
                $errors[] = 'ml_confidence fora da faixa [0, 1]';
            }
        }

        if (isset($payload['timestamp'])) {
            $timestamp = $payload['timestamp'];
            $parsedEpoch = null;

            if ($this->isFiniteNumber($timestamp)) {
                $parsedEpoch = (float) $timestamp;
            } elseif (is_string($timestamp) && trim($timestamp) !== '') {
                try {
                    $parsedEpoch = (float) Carbon::parse($timestamp)->timestamp;
                } catch (\Throwable) {
                    $parsedEpoch = null;
                }
            }

            if ($parsedEpoch === null || $parsedEpoch < 0.0) {
                $errors[] = 'timestamp inválido';
            } elseif ($parsedEpoch > now()->addDay()->timestamp) {
                $errors[] = 'timestamp no futuro além da tolerância';
            }
        }

        $tempCelsius = $this->extractTemperature($payload);
        if ($tempCelsius !== null) {
            if (! is_finite($tempCelsius) || $tempCelsius < -50.0 || $tempCelsius > 200.0) {
                $warnings[] = "temp_celsius descartada por leitura fisicamente inválida ({$tempCelsius})";
                $tempCelsius = null;
            }
        }

        $batteryVoltage = $this->extractBatteryVoltage($payload);
        if ($batteryVoltage !== null) {
            if (! is_finite($batteryVoltage) || $batteryVoltage <= 0.0 || $batteryVoltage > 30.0) {
                $warnings[] = "battery_voltage descartada por leitura fisicamente inválida ({$batteryVoltage})";
                $batteryVoltage = null;
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'sanitized' => [
                'temp_celsius' => $tempCelsius,
                'battery_voltage' => $batteryVoltage,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $errors
     */
    protected function validateNonNegativeFinite(
        mixed $value,
        string $field,
        array &$errors,
        ?float $max = null
    ): void {
        if ($value === null) {
            return;
        }

        if (! $this->isFiniteNumber($value)) {
            $errors[] = "{$field} não é numérico finito";

            return;
        }

        $numeric = (float) $value;
        if ($numeric < 0.0) {
            $errors[] = "{$field} não pode ser negativo";

            return;
        }

        if ($max !== null && $numeric > $max) {
            $errors[] = "{$field} excede limite físico ({$max})";
        }
    }

    protected function isFiniteNumber(mixed $value): bool
    {
        if (! is_int($value) && ! is_float($value) && ! is_numeric($value)) {
            return false;
        }

        return is_finite((float) $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractTemperature(array $payload): ?float
    {
        $raw = $payload['temp_celsius']
            ?? $payload['temperature']
            ?? $payload['features']['temperature']
            ?? $payload['features']['temp_celsius']
            ?? null;

        if ($raw === null) {
            return null;
        }

        if (! is_int($raw) && ! is_float($raw) && ! is_numeric($raw)) {
            return NAN;
        }

        return (float) $raw;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractBatteryVoltage(array $payload): ?float
    {
        $raw = $payload['battery_voltage']
            ?? $payload['v_bat']
            ?? $payload['features']['battery_voltage']
            ?? null;

        if ($raw === null) {
            return null;
        }

        if (! is_int($raw) && ! is_float($raw) && ! is_numeric($raw)) {
            return NAN;
        }

        return (float) $raw;
    }
}
