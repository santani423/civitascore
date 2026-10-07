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
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;
use Modules\HumanResource\Models\Concerns\GuardsEmployeeFiles;

/**
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property EducationLevel $level
 * @property string $institution
 * @property string|null $major
 * @property int|null $entry_year
 * @property int|null $graduation_year
 * @property string|null $certificate_number
 * @property string|null $gpa
 * @property string|null $document_file_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $documentFile
 */
class EmployeeEducation extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $table = 'employee_educations';

    protected $fillable = [
        'university_id', 'employee_id', 'level', 'institution', 'major', 'entry_year', 'graduation_year',
        'certificate_number', 'gpa', 'document_file_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => EducationLevel::class,
            'entry_year' => 'integer',
            'graduation_year' => 'integer',
            'gpa' => 'decimal:2',
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
