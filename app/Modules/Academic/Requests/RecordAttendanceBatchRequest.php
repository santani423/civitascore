<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Requests\Concerns\ValidatesAttendanceMeeting;
use Modules\Academic\Support\AcademicClock;

class RecordAttendanceBatchRequest extends FormRequest
{
    use ValidatesAttendanceMeeting;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * `reason` (opsional) dicatat AuditLogService sebagai alasan perubahan
     * pada setiap baris presensi yang diubah lewat permintaan ini.
     *
     * @return array<string, mixed>
     */
    public function rules(AcademicClock $clock): array
    {
        return [
            'meeting_number' => ['required', 'integer', 'min:1', 'max:255'],
            'meeting_date' => $this->meetingDateRules($clock),
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.krs_item_id' => ['required', 'string'],
            'entries.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'entries.*.notes' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateMeetingWithinTerm($validator));
    }
}
