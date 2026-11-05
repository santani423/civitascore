<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban peserta untuk satu butir (nilai skala 1–5). Satu baris per butir per sesi.
 *
 * @property string $id
 * @property string $university_id
 * @property string $session_id
 * @property string $item_id
 * @property int $value
 * @property CarbonImmutable $answered_at
 * @property-read AssessmentSession $session
 * @property-read AssessmentItem $item
 */
class AssessmentResponse extends PmbModel
{
    protected $table = 'pmb_assessment_responses';

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'answered_at' => 'datetime',
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
     * @return BelongsTo<AssessmentItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(AssessmentItem::class, 'item_id');
    }
}
