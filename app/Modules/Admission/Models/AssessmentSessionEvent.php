<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak kejadian sesi (dimulai, dikirim, kedaluwarsa, dipublikasikan).
 * Tanpa `updated_at`: baris bersifat append-only.
 *
 * @property string $id
 * @property string $university_id
 * @property string $session_id
 * @property string $event_type
 * @property string|null $actor
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $created_at
 * @property-read AssessmentSession $session
 */
class AssessmentSessionEvent extends PmbModel
{
    protected $table = 'pmb_assessment_session_events';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
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
}
