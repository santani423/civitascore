<?php

namespace Modules\UserManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route already carries `permission:roles.update`; the controller
        // additionally checks RolePolicy — this Request only validates shape.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
