<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewNote extends PmbModel
{
    protected $table = 'pmb_review_notes';

    protected function casts(): array
    {
        return [
            'target_fields' => 'array',
            'due_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
