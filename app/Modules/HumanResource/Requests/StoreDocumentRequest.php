<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\DocumentType;

/**
 * Berkasnya sendiri diunggah lebih dulu lewat POST /file-uploads (validasi
 * MIME pdf/jpg/png/doc/xls & batas ukuran dari system setting
 * file.max_upload_size_mb ada di sana); di sini hanya id-nya + metadata.
 */
class StoreDocumentRequest extends FormRequest
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
            'employee_id' => ['required', 'string'],
            'document_type' => ['required_without:replaces_document_id', Rule::enum(DocumentType::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file_upload_id' => ['required', 'string'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'replaces_document_id' => ['nullable', 'string'],
        ];
    }
}
