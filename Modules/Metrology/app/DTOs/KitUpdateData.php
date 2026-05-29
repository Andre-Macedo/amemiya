<?php

declare(strict_types=1);

namespace Modules\Metrology\DTOs;

class KitUpdateData
{
    /**
     * @param  array<KitItemUpdateData>  $items  Lista de atualizações para itens do kit.
     */
    public function __construct(
        public readonly array $items
    ) {}

    public static function fromArray(array $data): self
    {
        // $data espera ser o array de itens diretamente.
        // Baseado no uso em UpdateReferenceStandardKitAction: execute(Calibration $c, array $kitItemsResults)
        // $kitItemsResults itera como $itemData. Então é uma lista.

        $items = array_map(
            fn ($item) => KitItemUpdateData::fromArray($item),
            $data
        );

        return new self($items);
    }
}
