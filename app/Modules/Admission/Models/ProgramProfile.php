<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $study_program_id
 * @property string $slug
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $accreditation_label
 * @property CarbonImmutable|null $accreditation_valid_until
 * @property string|null $degree_title
 * @property string|null $study_duration
 * @property string|null $career_prospects
 * @property string|null $cover_file_id
 * @property string|null $brochure_file_id
 * @property bool $is_published
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StudyProgram $studyProgram
 * @property-read FileUpload|null $cover
 * @property-read FileUpload|null $brochure
 */
class ProgramProfile extends PmbModel
{
    protected $table = 'pmb_program_profiles';

    protected function casts(): array
    {
        return [
            'accreditation_valid_until' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'cover_file_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function brochure(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'brochure_file_id');
    }
}
