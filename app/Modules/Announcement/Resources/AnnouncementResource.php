<?php

namespace Modules\Announcement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Announcement\Models\Announcement;

/**
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'target_scope' => $this->target_scope->value,
            'target_id' => $this->target_id,
            'audience' => $this->audience,
            'target_admission_year' => $this->target_admission_year,
            'target_semester' => $this->target_semester,
            'is_pinned' => $this->is_pinned,
            'published_at' => $this->published_at->toIso8601String(),
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'attachment' => $this->whenLoaded('attachment', fn () => $this->attachment ? [
                'id' => $this->attachment->id,
                'name' => $this->attachment->original_name,
                'size_bytes' => $this->attachment->size_bytes,
                'mime_type' => $this->attachment->mime_type,
            ] : null),
            // Hanya ada pada feed mahasiswa (withExists('reads as is_read')).
            'is_read' => $this->when(array_key_exists('is_read', $this->resource->getAttributes()), fn () => (bool) $this->resource->getAttribute('is_read')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
