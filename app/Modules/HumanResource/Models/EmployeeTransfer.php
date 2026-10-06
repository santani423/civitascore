<?php

namespace Modules\HumanResource\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\TransferStatus;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;
use Modules\HumanResource\Models\Concerns\GuardsEmployeeFiles;

/**
 * Penempatan & mutasi. Unit/jabatan asal disalin saat mutasi dibuat
 * (snapshot), sehingga riwayat penempatan tetap terbaca walau master unit
 * atau jabatan berubah kemudian.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string|null $from_work_unit_id
 * @property string|null $from_work_unit_name
 * @property string|null $to_work_unit_id
 * @property string|null $to_work_unit_name
 * @property string|null $from_position_id
 * @property string|null $from_position_name
 * @property string|null $to_position_id
 * @property string|null $to_position_name
 * @property CarbonImmutable $effective_date
 * @property string|null $decree_number
 * @property string|null $document_file_id
 * @property string|null $reason
 * @property TransferStatus $status
 * @property CarbonImmutable|null $applied_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read WorkUnit|null $toWorkUnit
 * @property-read Position|null $toPosition
 * @property-read FileUpload|null $documentFile
 * @property-read User|null $creator
 */
class EmployeeTransfer extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'from_work_unit_id', 'from_work_unit_name', 'to_work_unit_id',
        'to_work_unit_name', 'from_position_id', 'from_position_name', 'to_position_id', 'to_position_name',
        'effective_date', 'decree_number', 'document_file_id', 'reason', 'status', 'applied_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'immutable_date',
            'status' => TransferStatus::class,
            'applied_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function toWorkUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'to_work_unit_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function documentFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'document_file_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
