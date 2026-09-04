<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class ChecklistCreationData
{
    /**
     * @param  string  $templateId  ID do template de checklist a ser usado.
     * @param  array<ChecklistItemData>  $items  Lista de itens a serem criados/respondidos.
     */
    public function __construct(
        public readonly string $templateId,
        public readonly array $items,
    ) {}

    /**
     * Cria uma instância a partir de um array de dados.
     *
     * @param  array  $data  Dados brutos (ex: request input).
     */
    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn ($item) => ChecklistItemData::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            templateId: (string) ($data['template_id'] ?? ''),
            items: $items,
        );
    }
}
