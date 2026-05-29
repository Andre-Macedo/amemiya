<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Metrology\Models\NonConformity;

class NonConformityApiController extends Controller
{
    public function index(Request $request)
    {
        $query = NonConformity::with(['item', 'calibration', 'opener']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show($id)
    {
        $nc = NonConformity::with(['item', 'calibration', 'opener', 'closer'])->findOrFail($id);
        return response()->json($nc);
    }

    public function update(Request $request, $id)
    {
        $nc = NonConformity::findOrFail($id);

        $validated = $request->validate([
            'root_cause_analysis' => 'nullable|string',
            'immediate_action' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'preventive_action' => 'nullable|string',
            'status' => 'required|in:open,investigating,resolved,closed',
        ]);

        $nc->update($validated);

        return response()->json($nc);
    }

    public function close(Request $request, $id)
    {
        $nc = NonConformity::findOrFail($id);

        // Validação: Só pode fechar se tiver Causa Raiz e Ação Corretiva
        if (empty($nc->root_cause_analysis) || empty($nc->corrective_action)) {
            return response()->json(['message' => 'Cannot close NC without Root Cause Analysis and Corrective Action.'], 422);
        }

        $nc->update([
            'status' => 'closed',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return response()->json($nc);
    }
}
