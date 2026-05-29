<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Actions\SaveChecklistTemplateAction;
use Modules\Metrology\Http\Requests\StoreChecklistTemplateRequest;
use Modules\Metrology\Http\Requests\UpdateChecklistTemplateRequest;
use Modules\Metrology\Http\Resources\ChecklistTemplateApiResource;
use Modules\Metrology\Models\ChecklistTemplate;

/**
 * API Controller for managing checklist templates (calibration procedures).
 */
class ChecklistTemplateApiController extends Controller
{
    /**
     * Lists all checklist templates with optional filtering and pagination.
     *
     * Args:
     *     request: The request with filtering (search) and pagination parameters.
     *
     * Returns:
     *     A collection of serialized procedure templates.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ChecklistTemplate::with(['items', 'instrumentType']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $perPage = (int) $request->input('per_page', 20);

        return ChecklistTemplateApiResource::collection($query->paginate($perPage));
    }

    /**
     * Shows a specific checklist template.
     *
     * Args:
     *     template: The template model instance.
     *
     * Returns:
     *     A serialized procedure template.
     */
    public function show(ChecklistTemplate $checklist_template): ChecklistTemplateApiResource
    {
        $checklist_template->load(['items', 'instrumentType']);

        return new ChecklistTemplateApiResource($checklist_template);
    }

    /**
     * Stores a new checklist template and its items.
     *
     * Args:
     *     request: The validated procedure data.
     *     action: The business logic for saving templates.
     *
     * Returns:
     *     A serialized representation of the created template.
     */
    public function store(StoreChecklistTemplateRequest $request, SaveChecklistTemplateAction $action): ChecklistTemplateApiResource
    {
        $template = $action->execute($request->validated());

        return new ChecklistTemplateApiResource($template->load('items'));
    }

    /**
     * Updates an existing checklist template and replaces its items.
     *
     * Args:
     *     request: The validated update data.
     *     checklist_template: The template to update.
     *     action: The business logic for saving templates.
     *
     * Returns:
     *     A serialized representation of the updated template.
     */
    public function update(
        UpdateChecklistTemplateRequest $request,
        ChecklistTemplate $checklist_template,
        SaveChecklistTemplateAction $action
    ): ChecklistTemplateApiResource {
        $template = $action->execute($request->validated(), $checklist_template);

        return new ChecklistTemplateApiResource($template->load('items'));
    }

    /**
     * Soft deletes a procedure template.
     *
     * Args:
     *     checklist_template: The template to remove.
     *
     * Returns:
     *     A success message.
     */
    public function destroy(ChecklistTemplate $checklist_template): JsonResponse
    {
        $checklist_template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }
}
