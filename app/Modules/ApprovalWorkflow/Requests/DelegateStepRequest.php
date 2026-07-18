<?php

namespace Modules\ApprovalWorkflow\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelegateStepRequest extends FormRequest
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
            'delegate_to' => ['required', Rule::exists('users', 'id')],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
