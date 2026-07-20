<?php

namespace Modules\Library\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Library\Models\Book;

/**
 * @mixin Book
 */
class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'isbn' => $this->isbn,
            'category' => $this->category,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'loans_count' => $this->whenCounted('loans'),
            'loans' => $this->whenLoaded('loans', fn () => BookLoanResource::collection($this->loans)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
