<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\AssignmentFactory;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $class_section_id
 * @property string $title
 * @property string|null $description
 * @property string|null $attachment_file_id
 * @property CarbonImmutable $due_at
 * @property bool $allow_late_submission
 * @property bool $allow_resubmission
 * @property int $max_file_size_mb
 * @property array<int, string>|null $allowed_extensions
 * @property string $max_score
 * @property bool $is_published
 * @property CarbonImmutable|null $published_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ClassSection $classSection
 * @property-read FileUpload|null $attachment
 * @property-read Collection<int, AssignmentSubmission> $submissions
 * @property-read User|null $creator
 */
class Assignment extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory, HasUlids, TenantScoped;

    /** Format berkas default bila dosen tidak membatasi sendiri. */
    public const DEFAULT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png'];

    protected $fillable = [
        'university_id', 'class_section_id', 'title', 'description', 'attachment_file_id', 'due_at',
        'allow_late_submission', 'allow_resubmission', 'max_file_size_mb', 'allowed_extensions',
        'max_score', 'is_published', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'allow_late_submission' => 'boolean',
            'allow_resubmission' => 'boolean',
            'max_file_size_mb' => 'integer',
            'allowed_extensions' => 'array',
            'max_score' => 'decimal:2',
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
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }

    /**
     * @return HasMany<AssignmentSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<int, string>
     */
    public function acceptedExtensions(): array
    {
        return $this->allowed_extensions !== null && $this->allowed_extensions !== []
            ? array_map('strtolower', $this->allowed_extensions)
            : self::DEFAULT_EXTENSIONS;
    }

    public function allowsFileAccess(User $user): bool
    {
        $access = app(ClassSectionAccess::class);

        return ($this->is_published && $access->isEnrolled($user, $this->class_section_id))
            || $access->canManage($user, $this->classSection, 'assignments.read');
    }

    protected static function newFactory(): AssignmentFactory
    {
        return AssignmentFactory::new();
    }
}
