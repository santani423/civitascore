<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rentang nilai huruf yang dapat dikonfigurasi per ujian (spec §8) — kalau
 * ujian tidak mengonfigurasi rentang sendiri, ExamService::resolveGrade()
 * jatuh kembali ke ambang batas default LetterGrade::fromScore().
 *
 * @property string $id
 * @property string $university_id
 * @property string $exam_id
 * @property string $grade
 * @property string $min_score
 * @property string $max_score
 * @property-read Exam $exam
 */
class ExamGradeRange extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'exam_id', 'grade', 'min_score', 'max_score'];

    protected function casts(): array
    {
        return [
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
