<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Material;

/**
 * Encapsulates the logic for creating a new material.
 */
class CreateMaterialAction
{
    public function execute(array $data): Material
    {
        return Material::create($data);
    }
}
