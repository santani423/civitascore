<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $context
 * @property string $visibility
 * @property string $body
 * @property array<mixed>|null $target_fields
 * @property CarbonImmutable|null $due_at
 * @property string|null $author_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $author
 */
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
