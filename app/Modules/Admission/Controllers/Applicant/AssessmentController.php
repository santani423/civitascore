<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Assessment\AssessmentSessionService;

/** One Atma Talent di portal pendaftar: daftar, persetujuan, pengerjaan, dan hasil (docs/one-atma-talent/01 §3). */
class AssessmentController extends ApplicantController
{
    public function __construct(private readonly AssessmentSessionService $sessions) {}

    public function catalog(Request $request, string $id): JsonResponse
    {
        $application = $this->application($request, $id);

        return ApiResponse::success(['assessments' => $this->sessions->catalog($application)]);
    }

    public function consent(Request $request, string $id): JsonResponse
    {
        $application = $this->application($request, $id);
        $this->sessions->consent($this->account($request), $application, $request->ip(), $request->userAgent());

        return ApiResponse::success(['consented' => true], 'Persetujuan pemrosesan data asesmen dicatat.');
    }

    public function start(Request $request, string $id, string $versionId): JsonResponse
    {
        $application = $this->application($request, $id);
        $version = AssessmentVersion::query()->whereKey($versionId)->firstOrFail();
        $session = $this->sessions->start($application, $version, $this->actorLabel($request));

        return ApiResponse::success($this->sessions->payload($session), 'Sesi asesmen siap dikerjakan.');
    }

    public function show(Request $request, string $sessionId): JsonResponse
    {
        return ApiResponse::success($this->sessions->payload($this->session($request, $sessionId)));
    }

    public function saveResponses(Request $request, string $sessionId): JsonResponse
    {
        $data = $request->validate([
            'responses' => ['required', 'array', 'min:1', 'max:500'],
            'responses.*.item_id' => ['required', 'string', 'size:26'],
            'responses.*.value' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $saved = $this->sessions->saveResponses($this->session($request, $sessionId), $data['responses']);

        return ApiResponse::success($saved + ['saved_at' => now()->toIso8601String()], 'Jawaban disimpan.');
    }

    public function submit(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->sessions->submit($this->session($request, $sessionId), $this->actorLabel($request));

        return ApiResponse::success($this->sessions->payload($session), 'Asesmen dikirim.');
    }

    public function result(Request $request, string $sessionId): JsonResponse
    {
        return ApiResponse::success($this->sessions->result($this->session($request, $sessionId)));
    }

    /** Sesi dicari lewat relasi akun (kepemilikan), 404 untuk milik orang lain. */
    private function session(Request $request, string $sessionId): AssessmentSession
    {
        return AssessmentSession::query()->whereKey($sessionId)
            ->whereIn('application_id', $this->account($request)->applications()->select('id'))
            ->firstOrFail();
    }

    private function actorLabel(Request $request): string
    {
        return 'applicant:'.$this->account($request)->getKey();
    }
}
