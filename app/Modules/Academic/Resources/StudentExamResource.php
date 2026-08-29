<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Exam;

/**
 * Tampilan daftar/detail ujian dari sudut pandang satu peserta (Portal
 * Mahasiswa) — menggabungkan konfigurasi ujian (aman untuk peserta, tidak
 * pernah menyertakan soal/opsi/is_correct) dengan status pengerjaan
 * mahasiswa yang sedang login, dihitung di StudentExamController lewat
 * ExamService::computeStudentStatus().
 *
 * @mixin Exam
 */
class StudentExamResource extends JsonResource
{
    /**
     * @param  array{attempts_used: int, status: string, result_visible: bool, score: string|null, latest_attempt_id: string|null}  $studentContext
     */
    public function __construct(Exam $exam, private readonly array $studentContext)
    {
        parent::__construct($exam);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'course_name' => $this->whenLoaded('classSection', fn () => $this->classSection?->course?->name),
            'lecturer_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'duration_minutes' => $this->duration_minutes,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'questions_per_participant' => $this->questions_per_participant,
            'allow_back_navigation' => $this->allow_back_navigation,
            'max_attempts' => $this->max_attempts,
            'attempts_used' => $this->studentContext['attempts_used'],
            'status' => $this->studentContext['status'],
            'result_visible' => $this->studentContext['result_visible'],
            'score' => $this->studentContext['score'],
            'latest_attempt_id' => $this->studentContext['latest_attempt_id'],
        ];
    }
}
