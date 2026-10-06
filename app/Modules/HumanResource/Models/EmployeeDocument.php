<?php

namespace Modules\HumanResource\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Enums\DocumentType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * Dokumen kepegawaian. Versioning sederhana: mengunggah ulang dokumen
 * membuat baris baru (version + 1, previous_version_id menunjuk versi
 * lama) dan menandai versi lama is_current = false — berkas lama tidak
 * dibuang.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property DocumentType $document_type
 * @property string|null $title
 * @property string|null $document_number
 * @property string $file_upload_id
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $expires_at
 * @property DocumentStatus $status
 * @property string|null $verification_note
 * @property string|null $verified_by
 * @property CarbonImmutable|null $verified_at
 * @property int $version
 * @property string|null $previous_version_id
 * @property bool $is_current
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $file
 * @property-read User|null $verifier
 */
class EmployeeDocument extends Model implements ScopesToInstitution
{
    use Auditable, BelongsToEmployee, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'document_type', 'title', 'document_number', 'file_upload_id', 'issued_at',
        'expires_at', 'status', 'verification_note', 'verified_by', 'verified_at', 'version', 'previous_version_id',
        'is_current', 'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'version' => 1,
        'is_current' => true,
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'issued_at' => 'immutable_date',
            'expires_at' => 'immutable_date',
            'verified_at' => 'immutable_datetime',
            'version' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
