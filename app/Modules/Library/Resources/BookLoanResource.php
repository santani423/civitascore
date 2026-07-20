<?php

namespace Modules\Library\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Library\Models\BookLoan;

/**
 * @mixin BookLoan
 */
class BookLoanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student?->name),
            'student_nim' => $this->whenLoaded('student', fn () => $this->student?->nim),
            'borrowed_at' => $this->borrowed_at->toDateString(),
            'due_at' => $this->due_at->toDateString(),
            'returned_at' => $this->returned_at?->toDateString(),
            'status' => $this->status->value,
            'fine_amount' => $this->fine_amount,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
