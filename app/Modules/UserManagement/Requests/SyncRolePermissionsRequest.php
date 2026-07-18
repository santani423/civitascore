<?php

namespace Modules\UserManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
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
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => [Rule::exists('permissions', 'id')],
        ];
    }
}
