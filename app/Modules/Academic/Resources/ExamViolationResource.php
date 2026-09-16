<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\ExamViolation;

/**
 * Satu baris linimasa pelanggaran (spec §5) — urutan/jenis/waktu/penalti.
 * Dipakai baik untuk timeline satu attempt (ExamAttemptController::violations)
 * maupun feed polling lintas peserta (ExamController::recentViolations).
 *
 * @mixin ExamViolation
 */
class ExamViolationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_attempt_id' => $this->exam_attempt_id,
            'sequence_number' => $this->sequence_number,
            'violation_type' => $this->violation_type->value,
            'violation_label' => $this->violation_type->label(),
            'penalty_points' => $this->penalty_points,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'student_name' => $this->whenLoaded('examAttempt', fn () => $this->examAttempt->krsItem->student->name),
            'student_nim' => $this->whenLoaded('examAttempt', fn () => $this->examAttempt->krsItem->student->nim),
        ];
    }
}
