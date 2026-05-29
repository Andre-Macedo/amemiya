<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Modules\System\Models\Station;

/**
 * Encapsulates the logic for updating an existing system station.
 */
class UpdateStationAction
{
    /**
     * Executes the station update process.
     *
     * Args:
     *     station: The station model to update.
     *     data: The validated station data.
     *
     * Returns:
     *     The updated Station model.
     */
    public function execute(Station $station, array $data): Station
    {
        $station->update($data);

        return $station;
    }
}
