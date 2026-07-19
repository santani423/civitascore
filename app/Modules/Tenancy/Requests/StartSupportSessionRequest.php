<?php

namespace Modules\Tenancy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSupportSessionRequest extends FormRequest
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
            'university_id' => ['required', 'string', 'exists:universities,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
