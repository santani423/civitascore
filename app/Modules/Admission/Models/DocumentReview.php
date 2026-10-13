<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ReviewDecision;

class DocumentReview extends PmbModel
{
    protected $table = 'pmb_document_reviews';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'decision' => ReviewDecision::class,
        ];
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
