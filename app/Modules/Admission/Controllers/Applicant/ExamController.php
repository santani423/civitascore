<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Enums\ExamScheduleStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\CbtAttempt;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Services\Exam\CbtService;
use Modules\Admission\Services\Exam\ExamService;
use Modules\Admission\Support\PmbFileStorage;

/** Jadwal, kartu, jadwal ulang, dan CBT pendaftar (docs/pmb/11 §A.10, POR-07). */
class ExamController extends ApplicantController
{
    public function index(Request $request, string $id): JsonResponse
    {
        $application = $this->application($request, $id);
        $participants = ExamParticipant::query()->with(['schedule', 'session.room.venue', 'cbtAttempt'])->where('application_id', $application->getKey())
            ->whereHas('schedule', fn ($q) => $q->whereIn('status', [ExamScheduleStatus::Published->value, ExamScheduleStatus::Completed->value]))->get();

        return ApiResponse::success([
            'requires_exam' => (bool) $application->admissionPath?->requires_exam,
            'exam_window' => ['starts_on' => $application->wave?->exam_starts_on?->toDateString(), 'ends_on' => $application->wave?->exam_ends_on?->toDateString()],
            'participations' => $participants->map(fn (ExamParticipant $p) => [
                'id' => $p->getKey(),
                'participant_number' => $p->participant_number,
                'title' => $p->schedule->title,
                'mode' => $p->schedule->mode->value,
                'instructions' => $p->schedule->instructions,
                'session' => ['code' => $p->session->code, 'starts_at' => $p->session->starts_at->toIso8601String(), 'ends_at' => $p->session->ends_at->toIso8601String(), 'online_url' => $p->session->online_url],
                'location' => $p->session->room ? ['venue' => $p->session->room->venue?->name, 'room' => $p->session->room->name, 'address' => $p->session->room->venue?->address, 'map_url' => $p->session->room->venue?->map_url] : null,
                'attendance_status' => $p->attendance_status->value,
                'card_available' => $p->card_file_id !== null,
                'reschedule_count' => $p->reschedule_count,
                'cbt' => $p->schedule->mode->value === 'cbt' ? ['started' => $p->cbtAttempt !== null, 'submitted' => $p->cbtAttempt?->submitted_at !== null, 'duration_minutes' => $p->schedule->cbt_duration_minutes] : null,
            ])->values(),
        ]);
    }

    public function card(Request $request, string $participantId, PmbFileStorage $storage): JsonResponse
    {
        $participant = $this->participant($request, $participantId);

        if ($participant->card_file_id === null) {
            throw new DomainRuleException('Kartu peserta belum terbit.', 'CARD_NOT_READY', 409);
        }

        return ApiResponse::success(['url' => $storage->temporaryUrl(\Modules\FileManagement\Models\FileUpload::query()->findOrFail($participant->card_file_id))]);
    }

    public function reschedule(Request $request, string $participantId, ExamService $exams, PmbFileStorage $storage): JsonResponse
    {
        $participant = $this->participant($request, $participantId);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000'], 'attachment' => ['nullable', 'file', 'max:2048']]);
        $fileId = $request->hasFile('attachment') ? $storage->store($request->file('attachment'), ['image/jpeg', 'image/png', 'application/pdf'], 2048, ['reschedule', (string) $participant->getKey()])->getKey() : null;
        $exams->requestReschedule($participant, $data['reason'], $fileId);

        return ApiResponse::success(null, 'Permintaan jadwal ulang dikirim.', status: 201);
    }

    public function cbtStart(Request $request, string $participantId, CbtService $cbt): JsonResponse
    {
        return ApiResponse::success($cbt->start($this->participant($request, $participantId)));
    }

    public function cbtAnswer(Request $request, string $participantId, CbtService $cbt): JsonResponse
    {
        $data = $request->validate(['question_id' => ['required', 'string', 'size:26'], 'option' => ['nullable', 'string', 'max:10']]);
        $cbt->answer($this->attempt($request, $participantId), $data['question_id'], $data['option'] ?? null);

        return ApiResponse::success(['saved_at' => now()->toIso8601String()]);
    }

    public function cbtViolation(Request $request, string $participantId, CbtService $cbt): JsonResponse
    {
        $cbt->recordViolation($this->attempt($request, $participantId));

        return response()->json(null, 204);
    }

    public function cbtSubmit(Request $request, string $participantId, CbtService $cbt): JsonResponse
    {
        $attempt = $cbt->submit($this->attempt($request, $participantId));

        return ApiResponse::success(['submitted_at' => $attempt->submitted_at?->toIso8601String()], 'Jawaban dikirim.');
    }

    private function participant(Request $request, string $participantId): ExamParticipant
    {
        return ExamParticipant::query()->with(['schedule', 'session'])->whereKey($participantId)
            ->whereIn('application_id', $this->account($request)->applications()->select('id'))->firstOrFail();
    }

    private function attempt(Request $request, string $participantId): CbtAttempt
    {
        return CbtAttempt::query()->where('participant_id', $this->participant($request, $participantId)->getKey())->firstOrFail();
    }
}
