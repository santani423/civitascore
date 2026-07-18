<?php

namespace Modules\SystemSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureFlagRequest extends FormRequest
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
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
