<?php

namespace Modules\FileManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SystemSetting\Services\SystemSettingService;

class StoreFileUploadRequest extends FormRequest
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
        $maxSizeMb = (int) app(SystemSettingService::class)->get('file.max_upload_size_mb', 10);

        return [
            'file' => [
                'required',
                'file',
                'max:'.($maxSizeMb * 1024),
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
