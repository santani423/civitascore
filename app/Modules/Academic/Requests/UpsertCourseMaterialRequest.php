<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\CourseMaterialType;

class UpsertCourseMaterialRequest extends FormRequest
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
            'type' => [$creating ? 'required' : 'sometimes', Rule::enum(CourseMaterialType::class)],
            'meeting_number' => ['nullable', 'integer', 'min:1', 'max:32'],
            'description' => ['nullable', 'string', 'max:10000'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'file_upload_id' => ['nullable', 'string', 'max:26'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'url.url' => 'Tautan harus diawali http:// atau https://.',
        ];
    }
}
