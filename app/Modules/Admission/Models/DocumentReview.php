<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ReviewDecision;

/**
 * @property string $id
 * @property string $university_id
 * @property string $document_version_id
 * @property ReviewDecision $decision
 * @property string|null $reason_code
 * @property string|null $note_for_applicant
 * @property string|null $internal_note
 * @property string $reviewed_by
 * @property CarbonImmutable|null $created_at
 * @property-read DocumentVersion $version
 * @property-read User $reviewer
 */
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
