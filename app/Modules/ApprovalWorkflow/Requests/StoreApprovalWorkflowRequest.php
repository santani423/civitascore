<?php

namespace Modules\ApprovalWorkflow\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;

class StoreApprovalWorkflowRequest extends FormRequest
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
            'workflowable_type' => ['required', 'string', 'max:150'],
            'conditions' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:150'],
            'steps.*.approver_type' => ['required', new Enum(ApprovalApproverType::class)],
            'steps.*.approver_role_id' => ['nullable', 'required_if:steps.*.approver_type,role', 'exists:roles,id'],
            'steps.*.approver_user_id' => ['nullable', 'required_if:steps.*.approver_type,user', 'exists:users,id'],
            // Tanpa `exists:positions` — tabel milik modul SDM dan ber-tenant;
            // jabatan yang tidak ditemukan resolver berarti tidak ada approver.
            'steps.*.approver_position_id' => ['nullable', 'required_if:steps.*.approver_type,position', 'string', 'size:26'],
            'steps.*.action_on_reject' => ['nullable', new Enum(ApprovalRejectAction::class)],
        ];
    }
}
