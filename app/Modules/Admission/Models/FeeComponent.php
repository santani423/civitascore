<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;

class FeeComponent extends PmbModel
{
    protected $table = 'pmb_fee_components';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_mandatory_for_enrollment' => 'boolean',
            'installable' => 'boolean',
            'installment_plan' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
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

    /**
     * @return BelongsTo<AdmissionPath, $this>
     */
    public function admissionPath(): BelongsTo
    {
        return $this->belongsTo(AdmissionPath::class, 'admission_path_id');
    }
}
