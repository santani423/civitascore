<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Existensi & isolasi tenant untuk student_id/class_section_id divalidasi
 * oleh AcademicRecordService::enroll() lewat findOrFail() ter-scope-tenant
 * (bukan Rule::exists di sini, yang query mentah dan akan bocor lintas
 * tenant kalau dipakai untuk FK model TenantScoped).
 */
class StoreKrsItemRequest extends FormRequest
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
            'student_id' => ['required', 'string'],
            'class_section_id' => ['required', 'string'],
        ];
    }
}
