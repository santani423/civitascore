<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Exam;

/**
 * Tampilan ujian di halaman akses publik (/exam/{access_token}, sebelum NIM
 * divalidasi) — hanya info identitas yang aman ditampilkan ke siapa pun yang
 * membuka link/scan QR, tidak pernah menyertakan soal/opsi/token apa pun.
 *
 * @mixin Exam
 */
class PublicExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'course_name' => $this->whenLoaded('classSection', fn () => $this->classSection?->course?->name),
            'lecturer_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'duration_minutes' => $this->duration_minutes,
            // ends_at & allow_back_navigation dipakai halaman pengerjaan
            // publik untuk mirror deadline & aturan navigasi soal seperti
            // Portal Mahasiswa (lihat StudentExam) — tetap tidak pernah
            // menyertakan soal/opsi/token.
            'ends_at' => $this->ends_at?->toIso8601String(),
            'allow_back_navigation' => $this->allow_back_navigation,
        ];
    }
}
