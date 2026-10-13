<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;

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
