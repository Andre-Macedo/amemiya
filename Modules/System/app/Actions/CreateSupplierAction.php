<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Modules\System\Models\Supplier;

/**
 * Encapsulates the logic for creating a new system supplier.
 */
class CreateSupplierAction
{
    /**
     * Executes the supplier creation process.
     *
     * Args:
     *     data: The validated supplier data.
     *
     * Returns:
     *     The newly created Supplier model.
     */
    public function execute(array $data): Supplier
    {
        return Supplier::create($data);
    }
}
