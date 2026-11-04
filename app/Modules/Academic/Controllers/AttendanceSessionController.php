<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\AttendanceSession;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Requests\OpenAttendanceSessionRequest;
use Modules\Academic\Services\AttendanceCheckInService;

/** Dosen membuka, memperbarui QR, dan menutup presensi mandiri per pertemuan. */
class AttendanceSessionController extends Controller
{
    public function __construct(private readonly AttendanceCheckInService $checkIns) {}

    public function store(OpenAttendanceSessionRequest $request, ClassSection $classSection): JsonResponse
    {
        $this->authorize('record', [Attendance::class, $classSection]);

        $session = $this->checkIns->open($classSection, $request->validated(), $request->user());

        return ApiResponse::success($this->present($session), 'Presensi mandiri dibuka.', status: 201);
    }

    public function qr(AttendanceSession $attendanceSession): JsonResponse
    {
        $this->authorize('record', [Attendance::class, $attendanceSession->classSection]);

        return ApiResponse::success($this->checkIns->currentQr($attendanceSession));
    }

    public function close(AttendanceSession $attendanceSession): JsonResponse
    {
        $this->authorize('record', [Attendance::class, $attendanceSession->classSection]);

        $session = $this->checkIns->close($attendanceSession);

        return ApiResponse::success($this->present($session), 'Presensi mandiri ditutup.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AttendanceSession $session): array
    {
        return [
            'id' => $session->id,
            'class_section_id' => $session->class_section_id,
            'meeting_number' => $session->meeting_number,
            'meeting_date' => $session->meeting_date->toDateString(),
            'expires_at' => $session->expires_at->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
        ];
    }
}
