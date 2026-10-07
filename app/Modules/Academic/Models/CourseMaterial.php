<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\CourseMaterialFactory;
use Modules\Academic\Enums\CourseMaterialType;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $class_section_id
 * @property int|null $meeting_number
 * @property string $title
 * @property string|null $description
 * @property CourseMaterialType $type
 * @property string|null $url
 * @property string|null $file_upload_id
 * @property bool $is_published
 * @property CarbonImmutable|null $published_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ClassSection $classSection
 * @property-read FileUpload|null $file
 * @property-read User|null $creator
 */
class CourseMaterial extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<CourseMaterialFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'class_section_id', 'meeting_number', 'title', 'description', 'type',
        'url', 'file_upload_id', 'is_published', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CourseMaterialType::class,
            'meeting_number' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<ClassSection, $this>
     */
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Peserta kelas (materi yang sudah dipublikasikan), dosen pengampu, atau Bagian Akademik. */
    public function allowsFileAccess(User $user): bool
    {
        $access = app(ClassSectionAccess::class);

        return ($this->is_published && $access->isEnrolled($user, $this->class_section_id))
            || $access->canManage($user, $this->classSection, 'course_materials.read');
    }

    protected static function newFactory(): CourseMaterialFactory
    {
        return CourseMaterialFactory::new();
    }
}
