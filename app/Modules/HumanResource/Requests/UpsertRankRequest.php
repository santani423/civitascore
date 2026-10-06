<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Models\Rank;

class UpsertRankRequest extends FormRequest
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
        /** @var Rank|null $current */
        $current = $this->route('rank');

        return [
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['required', 'string', 'max:20', Rule::unique('ranks', 'grade')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->id)],
            'level' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
