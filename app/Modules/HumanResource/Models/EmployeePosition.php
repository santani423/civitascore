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
use Modules\HumanResource\Enums\PositionType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;
use Modules\HumanResource\Models\Concerns\GuardsEmployeeFiles;

/**
 * Satu baris riwayat jabatan. Baris lama tidak pernah dihapus saat pegawai
 * berganti jabatan — hanya ditutup (end_date diisi, is_current = false)
 * oleh EmployeeHistoryService.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string|null $position_id
 * @property string $position_name
 * @property PositionType $position_type
 * @property string|null $work_unit_id
 * @property string|null $work_unit_name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $decree_number
 * @property string|null $decree_file_id
 * @property string|null $notes
 * @property bool $is_current
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Position|null $position
 * @property-read WorkUnit|null $workUnit
 * @property-read FileUpload|null $decreeFile
 */
class EmployeePosition extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'position_id', 'position_name', 'position_type', 'work_unit_id',
        'work_unit_name', 'start_date', 'end_date', 'decree_number', 'decree_file_id', 'notes', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'position_type' => PositionType::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function decreeFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'decree_file_id');
    }
}
