<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Metrology\Models\Attachment;

class AttachmentApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // Max 10MB
            'attachable_type' => ['required', 'string'],
            'attachable_id' => ['required', 'integer'],
        ]);

        $file = $request->file('file');

        // Resolve morphed class name if an alias is used, or use the class name directly
        $attachableClass = Relation::getMorphedModel($request->attachable_type) ?? $request->attachable_type;

        if (! class_exists($attachableClass)) {
            return response()->json(['message' => 'Invalid attachable type.'], 400);
        }

        $model = $attachableClass::findOrFail($request->attachable_id);

        $disk = 'public'; // Force public disk for frontend accessibility
        $path = $file->store('attachments', $disk);

        $attachment = $model->attachments()->create([
            'file_name' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'file_path' => $path,
            'disk' => $disk,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'attachment' => $attachment,
        ], 201);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        if (Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            Storage::disk($attachment->disk)->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }
}
