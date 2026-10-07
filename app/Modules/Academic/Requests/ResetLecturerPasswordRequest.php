<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetLecturerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Optional — when omitted a random temporary password is generated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}
