<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;
use Modules\HumanResource\Models\Concerns\GuardsEmployeeFiles;

/**
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string $name
 * @property string $issuer
 * @property string|null $certificate_number
 * @property CarbonImmutable $issued_at
 * @property CarbonImmutable|null $expires_at
 * @property string|null $document_file_id
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $documentFile
 */
class EmployeeCertification extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'name', 'issuer', 'certificate_number', 'issued_at', 'expires_at',
        'document_file_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_date',
            'expires_at' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function documentFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'document_file_id');
    }
}
