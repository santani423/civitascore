<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Services\LearningService;

class UpsertAssignmentRequest extends FormRequest
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
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'due_at' => [$creating ? 'required' : 'sometimes', 'date', ...($creating ? ['after:now'] : [])],
            'allow_late_submission' => ['sometimes', 'boolean'],
            'allow_resubmission' => ['sometimes', 'boolean'],
            'max_file_size_mb' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'allowed_extensions' => ['sometimes', 'nullable', 'array'],
            'allowed_extensions.*' => ['string', Rule::in(LearningService::SELECTABLE_EXTENSIONS)],
            'max_score' => ['sometimes', 'numeric', 'min:1', 'max:1000'],
            'is_published' => ['sometimes', 'boolean'],
            'attachment_file_id' => ['sometimes', 'nullable', 'string', 'max:26'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul tugas wajib diisi.',
            'due_at.required' => 'Batas waktu pengumpulan wajib diisi.',
            'due_at.after' => 'Batas waktu pengumpulan harus di masa mendatang.',
            'allowed_extensions.*.in' => 'Format berkas tidak dikenali.',
        ];
    }
}
