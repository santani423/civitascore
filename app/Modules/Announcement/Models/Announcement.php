<?php

namespace Modules\Announcement\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Announcement\Database\Factories\AnnouncementFactory;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Services\StudentAnnouncementFeed;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $title
 * @property string $body
 * @property string|null $attachment_file_id
 * @property AnnouncementTargetScope $target_scope
 * @property string|null $target_id
 * @property string $audience
 * @property int|null $target_admission_year
 * @property int|null $target_semester
 * @property bool $is_pinned
 * @property CarbonImmutable $published_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $creator
 * @property-read FileUpload|null $attachment
 */
class Announcement extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, HasUlids, TenantScoped;

    /** Sasaran peran pengumuman (kolom `audience`). */
    public const AUDIENCES = ['all', 'students', 'lecturers', 'staff'];

    protected $fillable = [
        'university_id', 'title', 'body', 'attachment_file_id', 'target_scope', 'target_id', 'audience',
        'target_admission_year', 'target_semester', 'is_pinned', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_scope' => AnnouncementTargetScope::class,
            'target_admission_year' => 'integer',
            'target_semester' => 'integer',
            'is_pinned' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }

    /**
     * @return HasMany<AnnouncementRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /** Lampiran: pengelola pengumuman, atau mahasiswa yang menjadi sasaran pengumuman ini. */
    public function allowsFileAccess(User $user): bool
    {
        if ($user->hasPermissionTo('announcements.read')) {
            return true;
        }

        $student = $user->student;

        return $student !== null && app(StudentAnnouncementFeed::class)->isVisibleTo($this, $student);
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
