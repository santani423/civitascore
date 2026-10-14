<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ExportStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $user_id
 * @property string $report_code
 * @property string $format
 * @property array<mixed>|null $filters
 * @property array<mixed>|null $columns
 * @property int $row_count
 * @property string $reason
 * @property string|null $file_upload_id
 * @property bool $contains_personal_data
 * @property ExportStatus $status
 * @property CarbonImmutable|null $expires_at
 * @property string|null $error_message
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read FileUpload|null $fileUpload
 */
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
