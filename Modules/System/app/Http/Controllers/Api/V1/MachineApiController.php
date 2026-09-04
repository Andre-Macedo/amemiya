<?php

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\System\Models\Machine;

class MachineApiController extends Controller
{
    public function index()
    {
        $machines = Machine::query()->latest()->get();

        return JsonResource::collection($machines);
    }
}
