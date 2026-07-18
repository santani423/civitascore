<?php

namespace Modules\UserManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
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
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
