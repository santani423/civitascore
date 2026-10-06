<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\HrRequestType;

/**
 * Pengajuan SDM non-cuti (cuti punya alurnya sendiri lewat
 * StoreLeaveRequest). Untuk perubahan data, nilai baru yang diminta
 * divalidasi di sini — sama ketatnya dengan form pegawai — supaya saat
 * disetujui perubahannya pasti bisa diterapkan.
 */
class StoreHrRequestRequest extends FormRequest
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
        $isSelfService = $this->is('*/me/*');
        $employeeId = $isSelfService
            ? Employee::query()->where('user_id', $this->user()?->id)->value('id')
            : $this->input('employee_id');

        return [
            'employee_id' => [$isSelfService ? 'prohibited' : 'required', 'string'],
            'type' => ['required', Rule::enum(HrRequestType::class)->except([HrRequestType::Leave])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment_file_id' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'payload.changes' => [Rule::requiredIf($this->input('type') === HrRequestType::DataChange->value), 'array'],
            'payload.changes.name' => ['sometimes', 'string', 'max:255'],
            'payload.changes.email' => ['sometimes', 'email', 'max:255'],
            'payload.changes.phone' => ['sometimes', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'payload.changes.address' => ['sometimes', 'string', 'max:1000'],
            'payload.changes.gender' => ['sometimes', Rule::enum(Gender::class)],
            'payload.changes.birth_place' => ['sometimes', 'string', 'max:100'],
            'payload.changes.birth_date' => ['sometimes', 'date', 'before:today'],
            'payload.changes.nik' => [
                'sometimes', 'digits:16',
                Rule::unique('employees', 'nik')->where('university_id', app(TenantContext::class)->universityId())->ignore($employeeId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.enum' => 'Pengajuan cuti dibuat lewat menu Cuti & Izin.',
            'payload.changes.required' => 'Isi minimal satu data yang ingin diubah.',
            'payload.changes.nik.unique' => 'NIK sudah terdaftar pada pegawai lain.',
            'payload.changes.nik.digits' => 'NIK harus 16 digit angka.',
        ];
    }
}
