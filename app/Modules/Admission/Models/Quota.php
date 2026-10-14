<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;

/**
 * @property string $id
 * @property string $university_id
 * @property string $period_id
 * @property string $admission_path_id
 * @property string $study_program_id
 * @property string $study_system_id
 * @property int $capacity
 * @property int $waitlist_capacity
 * @property string|null $min_final_score
 * @property array<mixed>|null $wave_caps
 * @property int $accepted_count
 * @property bool $is_offered
 * @property int $lock_version
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Period $period
 * @property-read AdmissionPath $admissionPath
 * @property-read StudyProgram $studyProgram
 * @property-read StudySystem $studySystem
 */
class Quota extends PmbModel
{
    protected $table = 'pmb_quotas';

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'waitlist_capacity' => 'integer',
            'min_final_score' => 'decimal:2',
            'wave_caps' => 'array',
            'accepted_count' => 'integer',
            'is_offered' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    /**
     * @return BelongsTo<AdmissionPath, $this>
     */
    public function admissionPath(): BelongsTo
    {
        return $this->belongsTo(AdmissionPath::class, 'admission_path_id');
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    /**
     * @return BelongsTo<StudySystem, $this>
     */
    public function studySystem(): BelongsTo
    {
        return $this->belongsTo(StudySystem::class, 'study_system_id');
    }
}
