<?php

namespace Modules\FileManagement\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FileManagement\Database\Factories\FileUploadFactory;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string|null $uploaded_by
 * @property string|null $fileable_type
 * @property string|null $fileable_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property string|null $checksum
 * @property FileUploadStatus $status
 * @property bool $is_public
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Model|\Eloquent|null $fileable
 * @property-read User|null $uploadedBy
 *
 * @method static \Modules\FileManagement\Database\Factories\FileUploadFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereChecksum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereFileableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereFileableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUpload withoutTrashed()
 *
 * @mixin \Eloquent
 */
class FileUpload extends Model implements ScopesToInstitution
{
    /** @use HasFactory<FileUploadFactory> */
    use HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'university_id', 'uploaded_by', 'fileable_type', 'fileable_id', 'disk', 'path',
        'original_name', 'mime_type', 'extension', 'size_bytes',
        'checksum', 'status', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'status' => FileUploadStatus::class,
            'is_public' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): FileUploadFactory
    {
        return FileUploadFactory::new();
    }
}
