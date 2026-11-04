<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Academic\Requests\Concerns\ValidatesAttendanceMeeting;
use Modules\Academic\Support\AcademicClock;

class OpenAttendanceSessionRequest extends FormRequest
{
    use ValidatesAttendanceMeeting;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(AcademicClock $clock): array
    {
        return [
            'meeting_number' => ['required', 'integer', 'min:1', 'max:255'],
            'meeting_date' => $this->meetingDateRules($clock),
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateMeetingWithinTerm($validator));
    }
}
