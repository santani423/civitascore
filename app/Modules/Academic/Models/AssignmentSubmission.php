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
use Modules\Academic\Database\Factories\AssignmentSubmissionFactory;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $assignment_id
 * @property string $krs_item_id
 * @property string|null $file_upload_id
 * @property string|null $notes
 * @property CarbonImmutable $submitted_at
 * @property bool $is_late
 * @property int $submission_count
 * @property string|null $score
 * @property string|null $feedback
 * @property CarbonImmutable|null $graded_at
 * @property string|null $graded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Assignment $assignment
 * @property-read KrsItem $krsItem
 * @property-read FileUpload|null $file
 * @property-read User|null $grader
 */
class AssignmentSubmission extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<AssignmentSubmissionFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'assignment_id', 'krs_item_id', 'file_upload_id', 'notes', 'submitted_at',
        'is_late', 'submission_count', 'score', 'feedback', 'graded_at', 'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'submission_count' => 'integer',
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
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
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * @return BelongsTo<KrsItem, $this>
     */
    public function krsItem(): BelongsTo
    {
        return $this->belongsTo(KrsItem::class);
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
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** Mahasiswa pemiliknya, dosen pengampu kelas, atau Bagian Akademik. */
    public function allowsFileAccess(User $user): bool
    {
        $student = $user->student;

        if ($student !== null && $this->krsItem->student_id === $student->id) {
            return true;
        }

        return app(ClassSectionAccess::class)->canManage($user, $this->assignment->classSection, 'assignment_submissions.read');
    }

    protected static function newFactory(): AssignmentSubmissionFactory
    {
        return AssignmentSubmissionFactory::new();
    }
}
