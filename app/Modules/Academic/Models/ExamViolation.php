<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Enums\ExamViolationType;

/**
 * Satu kejadian pelanggaran selama satu percobaan ujian (spec §4) — dicatat
 * oleh StudentExamController/PublicExamController lewat
 * ExamService::recordViolation(), tidak pernah dari input klien selain
 * jenis pelanggaran itu sendiri (attempt diresolusi dari kepemilikan yang
 * sudah tervalidasi, sama seperti answerAttempt()).
 *
 * @property string $id
 * @property string $university_id
 * @property string $exam_attempt_id
 * @property ExamViolationType $violation_type
 * @property int $sequence_number
 * @property string $penalty_points
 * @property CarbonImmutable $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property-read ExamAttempt $examAttempt
 */
class ExamViolation extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'exam_attempt_id', 'violation_type', 'sequence_number', 'penalty_points', 'occurred_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'violation_type' => ExamViolationType::class,
            'sequence_number' => 'integer',
            'penalty_points' => 'decimal:2',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }
}
