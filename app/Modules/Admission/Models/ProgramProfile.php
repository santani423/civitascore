<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;
use Modules\FileManagement\Models\FileUpload;

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
