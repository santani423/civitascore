<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property int $priority
 * @property string $quota_id
 * @property string $study_program_id
 * @property string $study_system_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read Quota $quota
 * @property-read StudyProgram $studyProgram
 * @property-read StudySystem $studySystem
 */
class ApplicationChoice extends PmbModel
{
    protected $table = 'pmb_application_choices';

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<Quota, $this>
     */
    public function quota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'quota_id');
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
