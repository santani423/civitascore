<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Hanya field yang boleh diubah sendiri oleh mahasiswa (lihat
 * StudentProfileService::EDITABLE_FIELDS). Field lain di request diabaikan
 * — validated() tidak pernah memuat NIM/prodi/status/nama/email.
 */
class UpdateStudentProfileRequest extends FormRequest
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
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,30}$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'photo_file_id' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, atau tanda kurung.',
            'address.max' => 'Alamat maksimal 500 karakter.',
        ];
    }
}
