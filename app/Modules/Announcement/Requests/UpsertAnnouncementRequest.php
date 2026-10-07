<?php

namespace Modules\Announcement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;

/**
 * Keberadaan target_id (fakultas/prodi) di tenant yang sama dicek di
 * AnnouncementController lewat query ter-scope tenant, bukan Rule::exists.
 */
class UpsertAnnouncementRequest extends FormRequest
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
            'body' => [$creating ? 'required' : 'sometimes', 'string', 'max:20000'],
            'target_scope' => [$creating ? 'required' : 'sometimes', Rule::enum(AnnouncementTargetScope::class)],
            'target_id' => ['nullable', 'string', 'max:26', Rule::requiredIf(fn () => in_array($this->input('target_scope'), ['fakultas', 'program_studi'], true))],
            'audience' => ['sometimes', Rule::in(Announcement::AUDIENCES)],
            'target_admission_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'target_semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'is_pinned' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'attachment_file_id' => ['sometimes', 'nullable', 'string', 'max:26'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'body.required' => 'Isi pengumuman wajib diisi.',
            'target_id.required' => 'Pilih fakultas/program studi sasaran.',
        ];
    }
}
