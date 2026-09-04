<?php

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Models\Machine;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineApiController extends Controller
{
    public function index()
    {
        $machines = Machine::query()->latest()->get();
        return JsonResource::collection($machines);
    }
}
