<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Support\PortalFormatter;

/**
 * Pengajuan KRS dari sudut pandang penyetuju (dosen wali / Bagian Akademik).
 *
 * @mixin KrsSubmission
 */
class KrsSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_credits' => $this->total_credits,
            'max_credits' => $this->max_credits,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decided_by' => $this->whenLoaded('decider', fn () => $this->decider?->name),
            'decision_note' => $this->decision_note,
            'term' => $this->whenLoaded('academicTerm', fn () => PortalFormatter::term($this->academicTerm)),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'nim' => $this->student->nim,
                'name' => $this->student->name,
                'admission_year' => $this->student->admission_year,
                'status' => $this->student->status->value,
                'study_program' => $this->student->relationLoaded('studyProgram') ? $this->student->studyProgram?->name : null,
                'academic_advisor' => $this->student->relationLoaded('academicAdvisor') ? PortalFormatter::lecturer($this->student->academicAdvisor) : null,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items
                ->map(fn (KrsItem $item): array => [
                    'id' => $item->id,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'class_section' => PortalFormatter::classSection($item->classSection),
                ])
                ->values()
                ->all()),
        ];
    }
}
