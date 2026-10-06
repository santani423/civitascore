<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\TrainingStatus;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string $name
 * @property string $organizer
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $duration_hours
 * @property string|null $location
 * @property string|null $cost
 * @property TrainingStatus $status
 * @property string|null $certificate_file_id
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $certificateFile
 */
class EmployeeTraining extends Model implements ScopesToInstitution
{
    use Auditable, BelongsToEmployee, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'name', 'organizer', 'start_date', 'end_date', 'duration_hours', 'location',
        'cost', 'status', 'certificate_file_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TrainingStatus::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'duration_hours' => 'decimal:1',
            'cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function certificateFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'certificate_file_id');
    }
}
