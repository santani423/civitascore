<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceQrRequest extends FormRequest
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
            'payload' => ['required', 'string', 'max:100'],
        ];
    }
}
