<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Modules\System\Models\Station;

/**
 * Encapsulates the logic for creating a new system station.
 */
class CreateStationAction
{
    /**
     * Executes the station creation process.
     *
     * Args:
     *     data: The validated station data.
     *
     * Returns:
     *     The newly created Station model.
     */
    public function execute(array $data): Station
    {
        return Station::create($data);
    }
}
