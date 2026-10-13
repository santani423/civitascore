<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Support\PmbFileStorage;

/**
 * Hasil seleksi pendaftar (FR-ANN-04, J.3): keputusan `unpublished` tidak
 * pernah dikembalikan; sebelum terbit → 404 identik "belum ada hasil".
 */
class ResultController extends ApplicantController
{
    public function result(Request $request, string $id, PublicationService $publication): JsonResponse
    {
        $application = $this->application($request, $id);
        // Pemicu malas: publikasi tepat waktu walau scheduler server tidak berjalan.
        rescue(fn () => $publication->publishDue(), report: false);

        $decision = AdmissionDecision::query()->published()->where('application_id', $application->getKey())->where('is_current', true)
            ->with(['studyProgram', 'studySystem', 'letter'])->first();

        if ($decision === null) {
            return ApiResponse::error('Hasil seleksi belum tersedia.', ['announcement_at' => $application->wave?->announcement_at?->toIso8601String()], 404);
        }

        $previous = AdmissionDecision::query()->where('application_id', $application->getKey())->where('decision_version', '<', $decision->decision_version)
            ->where('publication_status', 'superseded')->latest('decision_version')->first();

        return ApiResponse::success([
            'decision' => $decision->decision->value,
            'version' => $decision->decision_version,
            'program' => $decision->studyProgram?->name,
            'study_system' => $decision->studySystem?->name,
            // Posisi cadangan tidak ditampilkan dalam angka pasti (POR-08 [K]).
            'waitlisted' => $decision->decision === Decision::Waitlisted,
            'published_at' => $decision->published_at?->toIso8601String(),
            'corrected' => $previous !== null,
            'correction_note' => $previous !== null ? $decision->correction_note : null,
            'letter_available' => $decision->letter?->status?->value === 'issued',
            're_registration' => $application->reRegistration ? [
                'status' => $application->reRegistration->status->value,
                'deadline_at' => $application->reRegistration->effectiveDeadline()->toIso8601String(),
                'opens_at' => $application->wave?->re_registration_opens_at?->toIso8601String(),
            ] : null,
            'scholarship_award' => $decision->scholarship_award ? ['percent' => $decision->scholarship_award['percent'] ?? null] : null,
        ]);
    }

    public function letter(Request $request, string $id, PmbFileStorage $storage): JsonResponse
    {
        $application = $this->application($request, $id);
        $decision = AdmissionDecision::query()->published()->where('application_id', $application->getKey())->where('is_current', true)->with('letter.fileUpload')->first();
        $letter = $decision?->letter;

        if ($letter === null || $letter->status->value !== 'issued' || $letter->fileUpload === null) {
            throw new DomainRuleException('Surat penerimaan belum terbit.', 'LETTER_NOT_READY', 409);
        }

        return ApiResponse::success(['url' => $storage->temporaryUrl($letter->fileUpload), 'letter_number' => $letter->letter_number]);
    }
}
