<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Hanya kelas yang dipilih — mahasiswa selalu diresolusi dari akun yang
 * login. Keberadaan & kelayakan kelas divalidasi KrsPlanService::addItem()
 * lewat query ter-scope tenant (bukan Rule::exists yang bocor lintas tenant).
 */
class AddKrsItemRequest extends FormRequest
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
            'class_section_id' => ['required', 'string', 'max:26'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['class_section_id.required' => 'Pilih kelas yang ingin diambil.'];
    }
}
