<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Models\EmployeeContract;

class UpsertContractRequest extends FormRequest
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
        /** @var EmployeeContract|null $current */
        $current = $this->route('contract');

        return [
            'employee_id' => [$current === null ? 'required' : 'prohibited', 'string'],
            'contract_number' => ['required', 'string', 'max:100', Rule::unique('employee_contracts', 'contract_number')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->id)],
            'contract_type' => ['required', Rule::enum(ContractType::class)],
            'start_date' => ['required', 'date'],
            // PKWT wajib bertanggal akhir; PKWTT (tetap) boleh kosong.
            'end_date' => [Rule::requiredIf($this->input('contract_type') === ContractType::Pkwt->value), 'nullable', 'date', 'after:start_date'],
            'document_file_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_number.unique' => 'Nomor kontrak sudah dipakai.',
            'end_date.required' => 'Kontrak PKWT wajib memiliki tanggal berakhir.',
            'end_date.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ];
    }
}
