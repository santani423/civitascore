<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\StudentRequestType;

/**
 * Validasi bentuk umum pengajuan. Aturan per jenis (status mahasiswa,
 * field perubahan data, jenis surat) ada di StudentRequestService.
 */
class StoreStudentRequestRequest extends FormRequest
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
        $creating = $this->isMethod('post');

        return [
            'type' => [$creating ? 'required' : 'prohibited', Rule::enum(StudentRequestType::class)],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'min:5', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payload' => ['sometimes', 'nullable', 'array'],
            'attachment_file_id' => ['sometimes', 'nullable', 'string', 'max:26'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Pilih jenis pengajuan.',
            'title.required' => 'Judul pengajuan wajib diisi.',
            'title.min' => 'Judul pengajuan minimal 5 karakter.',
            'type.prohibited' => 'Jenis pengajuan tidak dapat diubah.',
        ];
    }
}
