<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service edit by the logged-in dosen. Identity/employment fields
 * (NIDN, NIP, nama, email, fakultas, status kepegawaian, jabatan
 * fungsional) are deliberately absent — those are SDM's master data and
 * keep the login account in sync, so they're changed through SDM only.
 */
class UpdateLecturerProfileRequest extends FormRequest
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
            'phone' => ['present', 'nullable', 'string', 'max:30'],
        ];
    }
}
