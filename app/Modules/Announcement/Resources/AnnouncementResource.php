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
            'is_pinned' => $this->is_pinned,
            'published_at' => $this->published_at->toIso8601String(),
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
