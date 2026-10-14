<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;

/**
 * @property string $id
 * @property string $university_id
 * @property string $period_id
 * @property string $study_program_id
 * @property string $study_system_id
 * @property string|null $admission_path_id
 * @property string $component_code
 * @property string $name
 * @property string $amount
 * @property bool $is_mandatory_for_enrollment
 * @property bool $installable
 * @property array<mixed>|null $installment_plan
 * @property bool $is_published
 * @property int $sort_order
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Period $period
 * @property-read StudyProgram $studyProgram
 * @property-read StudySystem $studySystem
 * @property-read AdmissionPath|null $admissionPath
 */
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
