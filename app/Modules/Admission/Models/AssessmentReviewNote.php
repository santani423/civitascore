<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\AssessmentReviewNoteKind;

/**
 * Riwayat review sesi: catatan, penandaan, dan penyelesaian review. Append-only.
 *
 * @property string $id
 * @property string $university_id
 * @property string $session_id
 * @property string|null $author_id
 * @property AssessmentReviewNoteKind $kind
 * @property string|null $body
 * @property CarbonImmutable $created_at
 * @property-read AssessmentSession $session
 */
class AssessmentReviewNote extends PmbModel
{
    protected $table = 'pmb_assessment_review_notes';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'kind' => AssessmentReviewNoteKind::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AssessmentSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
