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
use Modules\Academic\Database\Factories\StudentRequestFactory;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property StudentRequestType $type
 * @property StudentRequestStatus $status
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $payload
 * @property string|null $attachment_file_id
 * @property string|null $academic_term_id
 * @property string|null $approval_request_id
 * @property string|null $requested_by
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decided_by
 * @property string|null $decision_note
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read AcademicTerm|null $academicTerm
 * @property-read ApprovalRequest|null $approvalRequest
 * @property-read FileUpload|null $attachment
 * @property-read User|null $decider
 */
class StudentRequest extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<StudentRequestFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'type', 'status', 'title', 'description', 'payload',
        'attachment_file_id', 'academic_term_id', 'approval_request_id', 'requested_by',
        'submitted_at', 'decided_at', 'decided_by', 'decision_note', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StudentRequestType::class,
            'status' => StudentRequestStatus::class,
            'payload' => 'array',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Mahasiswa pemiliknya, atau peninjau pengajuan (approval_requests.read). */
    public function allowsFileAccess(User $user): bool
    {
        return $this->student->user_id === $user->id || $user->hasPermissionTo('approval_requests.read');
    }

    protected static function newFactory(): StudentRequestFactory
    {
        return StudentRequestFactory::new();
    }
}
