<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Modules\System\Models\Supplier;

/**
 * Encapsulates the logic for updating an existing system supplier.
 */
class UpdateSupplierAction
{
    /**
     * Executes the supplier update process.
     *
     * Args:
     *     supplier: The supplier model to update.
     *     data: The validated supplier data.
     *
     * Returns:
     *     The updated Supplier model.
     */
    public function execute(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }
}
