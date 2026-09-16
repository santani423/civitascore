<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Enums\ExamAttemptStatus;

/**
 * Status keikutsertaan seorang peserta (KrsItem) pada satu ujian — dipakai
 * dosen/pengawas untuk melihat siapa yang belum/sedang/sudah mengerjakan.
 * `examAttempts` pada model harus sudah di-eager-load dan dibatasi ke
 * exam_id yang bersangkutan, diurutkan terbaru lebih dulu (lihat
 * ExamAttemptController::index).
 *
 * @mixin \Modules\Academic\Models\KrsItem
 */
class ExamParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestAttempt = $this->examAttempts->first();

        $status = match (true) {
            $latestAttempt === null => 'not_started',
            $latestAttempt->status === ExamAttemptStatus::InProgress => 'in_progress',
            default => 'completed',
        };

        return [
            'krs_item_id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student->name,
            'student_nim' => $this->student->nim,
            'attempts_used' => $this->examAttempts->count(),
            'status' => $status,
            'score' => $latestAttempt?->score,
            'raw_score' => $latestAttempt?->raw_score,
            'penalty_score' => $latestAttempt?->penalty_score,
            'grade' => $latestAttempt?->grade,
            'weighted_score' => $latestAttempt?->weighted_score,
            'violation_count' => $latestAttempt?->violations_count ?? 0,
            'latest_attempt_id' => $latestAttempt?->id,
            'submitted_at' => $latestAttempt?->submitted_at?->toIso8601String(),
        ];
    }
}
