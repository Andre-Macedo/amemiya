<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\System\Http\Resources\AccessLogApiResource;
use Modules\System\Models\AccessLog;

class AccessLogApiController extends Controller
{
    /**
     * Display a paginated listing of system access logs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = AccessLog::query()
            ->with(['user', 'station', 'instrument'])
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('station_id')) {
            $query->where('station_id', $request->input('station_id'));
        }

        if ($request->filled('instrument_id')) {
            $query->where('instrument_id', $request->input('instrument_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->paginate($perPage);

        return AccessLogApiResource::collection($logs);
    }
}
