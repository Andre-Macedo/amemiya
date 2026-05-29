<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class ChecklistItemData
{
    /**
     * @param  string  $step  Descrição do passo ou pergunta.
     * @param  string  $questionType  Tipo da pergunta (text, numeric, boolean, etc).
     * @param  int  $order  Ordem de exibição.
     * @param  int  $requiredReadings  Número de leituras necessárias (se numérico).
     * @param  array|null  $asFoundReadings  Leituras coletadas no recebimento.
     * @param  array|null  $asLeftReadings  Leituras coletadas após ajuste (se houver).
     * @param  bool  $adjusted  Indica se houve ajuste na bancada para este ponto.
     * @param  string|null  $result  Resultado (Aprovado/Reprovado) se aplicável.
     * @param  string|null  $uncertainty  Incerteza associada.
     * @param  string|null  $notes  Observações.
     * @param  int|null  $referenceStandardId  ID do padrão utilizado.
     */
    public function __construct(
        public readonly string $step,
        public readonly string $questionType,
        public readonly int $order,
        public readonly int $requiredReadings = 0,
        public readonly ?array $asFoundReadings = [],
        public readonly ?array $asLeftReadings = [],
        public readonly bool $adjusted = false,
        public readonly ?string $result = null,
        public readonly ?string $uncertainty = null,
        public readonly ?string $notes = null,
        public readonly ?int $referenceStandardId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            step: (string) ($data['step'] ?? ''),
            questionType: (string) ($data['question_type'] ?? 'text'),
            order: (int) ($data['order'] ?? 0),
            requiredReadings: (int) ($data['required_readings'] ?? 0),
            asFoundReadings: $data['as_found_readings'] ?? ($data['readings'] ?? []), // Fallback to 'readings' for legacy API compatibility
            asLeftReadings: $data['as_left_readings'] ?? [],
            adjusted: (bool) ($data['adjusted'] ?? false),
            result: isset($data['result']) ? (string) $data['result'] : null,
            uncertainty: isset($data['uncertainty']) ? (string) $data['uncertainty'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            referenceStandardId: isset($data['reference_standard_id']) ? (int) $data['reference_standard_id'] : null,
        );
    }
}
