<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Material;

/**
 * Encapsulates the logic for updating an existing material.
 */
class UpdateMaterialAction
{
    public function execute(Material $material, array $data): Material
    {
        $material->update($data);

        return $material;
    }
}
