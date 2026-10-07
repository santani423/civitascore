<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Berkas tugas diunggah dulu lewat POST /file-uploads; di sini cukup id-nya.
 * Format & ukuran berkas divalidasi terhadap aturan tugas itu sendiri di
 * LearningService::submit() (FileAttacher).
 */
class SubmitAssignmentRequest extends FormRequest
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
            'file_upload_id' => ['nullable', 'string', 'max:26'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
