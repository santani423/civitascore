<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\LeaveType;

/**
 * Dipakai SDM (mengajukan atas nama pegawai — employee_id wajib) maupun
 * pegawai sendiri lewat self-service (employee_id diabaikan; selalu
 * pegawai milik akun yang login).
 */
class StoreLeaveRequest extends FormRequest
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
            'employee_id' => [$this->is('*/me/*') ? 'prohibited' : 'required', 'string'],
            'leave_type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'attachment_file_id' => [Rule::requiredIf($this->input('leave_type') === LeaveType::Sick->value && $this->boolean('submit')), 'nullable', 'string'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'attachment_file_id.required' => 'Cuti sakit wajib melampirkan surat keterangan dokter.',
        ];
    }
}
