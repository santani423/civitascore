<?php

namespace Modules\Tenancy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertUniversitySettingRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:150'],
            'value' => ['required', 'string'],
            'type' => ['required', 'in:string,integer,boolean,json'],
            'group' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
