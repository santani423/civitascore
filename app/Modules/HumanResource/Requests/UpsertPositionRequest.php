<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\PositionType;
use Modules\HumanResource\Models\Position;

class UpsertPositionRequest extends FormRequest
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
        /** @var Position|null $current */
        $current = $this->route('position');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PositionType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
