<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ExportStatus;
use Modules\FileManagement\Models\FileUpload;

class ExportLog extends PmbModel
{
    protected $table = 'pmb_export_logs';

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'columns' => 'array',
            'row_count' => 'integer',
            'contains_personal_data' => 'boolean',
            'status' => ExportStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }
}
