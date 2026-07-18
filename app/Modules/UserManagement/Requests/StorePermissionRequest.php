<?php

namespace Modules\UserManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;

class StorePermissionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'resource' => ['required', 'string', 'max:100'],
            'scope' => ['required', new Enum(PermissionScope::class)],
            'action' => ['required', new Enum(PermissionAction::class)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
