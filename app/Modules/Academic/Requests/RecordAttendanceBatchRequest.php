<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\AttendanceStatus;

class RecordAttendanceBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'meeting_number' => ['required', 'integer', 'min:1', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.krs_item_id' => ['required', 'string'],
            'entries.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'entries.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
