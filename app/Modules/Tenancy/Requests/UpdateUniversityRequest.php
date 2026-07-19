<?php

namespace Modules\Tenancy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversityRequest extends FormRequest
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
        $university = $this->route('university');

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('universities', 'code')->ignore($university)],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('universities', 'slug')->ignore($university)],
            'name' => ['sometimes', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'education_institution_type' => ['nullable', 'string', 'max:100'],
            'accreditation' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:10'],
            'currency' => ['nullable', 'string', 'max:10'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
