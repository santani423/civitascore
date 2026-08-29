<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Modules\Academic\Models\ExamAttempt;

/**
 * Tampilan attempt untuk halaman pengerjaan ujian mahasiswa — mewarisi
 * ExamAttemptResource (sudah aman: tidak pernah menyertakan `is_correct`)
 * dan menambah aturan visibilitas nilai sesuai `exam.show_result_after_submission`
 * (spec §6 "Waiting for Result") tanpa menduplikasi pemetaan field lainnya.
 *
 * @mixin ExamAttempt
 */
class StudentExamAttemptResource extends ExamAttemptResource
{
    public function __construct(ExamAttempt $attempt, private readonly bool $resultVisible)
    {
        parent::__construct($attempt);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['result_visible'] = $this->resultVisible;

        if (! $this->resultVisible) {
            $data['score'] = null;
        }

        return $data;
    }
}
