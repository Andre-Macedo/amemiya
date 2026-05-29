<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Material;

class MaterialApiController extends Controller
{
    public function index()
    {
        return response()->json(
            Material::orderBy('name')->get()
        );
    }
}
