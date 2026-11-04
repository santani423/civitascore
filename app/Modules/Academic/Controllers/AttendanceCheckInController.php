<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Requests\ScanAttendanceQrRequest;
use Modules\Academic\Services\AttendanceCheckInService;

/** Portal Mahasiswa: presensi mandiri dengan memindai QR dosen. */
class AttendanceCheckInController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(private readonly AttendanceCheckInService $checkIns) {}

    public function scan(ScanAttendanceQrRequest $request): JsonResponse
    {
        $student = $this->currentStudent($request);

        $result = $this->checkIns->checkIn($student, $request->validated('payload'));

        $session = $result['session'];
        $attendance = $result['attendance'];

        $data = [
            'course_name' => $session->classSection->course->name,
            'course_code' => $session->classSection->course->code,
            'class_code' => $session->classSection->class_code,
            'meeting_number' => $session->meeting_number,
            'meeting_date' => $session->meeting_date->toDateString(),
            'status' => $attendance->status->value,
            'status_label' => $attendance->status->label(),
            'recorded_at' => $attendance->created_at->toIso8601String(),
            'already_recorded' => ! $result['recorded'],
        ];

        if (! $result['recorded']) {
            return ApiResponse::success($data, 'Kehadiran Anda sudah tercatat.', meta: ['code' => 'ALREADY_RECORDED']);
        }

        return ApiResponse::success($data, 'Kehadiran Anda berhasil dicatat.', status: 201);
    }
}
