<?php

namespace Modules\FileManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\FileManagement\Models\FileUpload;

/**
 * @mixin FileUpload
 */
class FileUploadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => $this->size_bytes,
            'status' => $this->status->value,
            'is_public' => $this->is_public,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
