<?php

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required_without:nim', 'nullable', 'email'],
            'nim' => ['required_without:email', 'nullable', 'string', 'max:50'],
            // Fallback tenant hint for NIM login when the university can't be
            // resolved from the request domain/X-University-ID header (e.g.
            // local dev, or a single shared frontend domain). Ignored for
            // email login.
            'university_code' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'device_identifier' => ['nullable', 'string', 'max:191'],
        ];
    }
}
