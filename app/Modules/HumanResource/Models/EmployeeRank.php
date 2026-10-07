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
 * Riwayat kepangkatan (kenaikan pangkat/golongan).
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string|null $rank_id
 * @property string $rank_name
 * @property string $grade
 * @property string|null $decree_number
 * @property CarbonImmutable|null $decree_date
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $decree_file_id
 * @property bool $is_current
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Rank|null $rank
 * @property-read FileUpload|null $decreeFile
 */
class EmployeeRank extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'rank_id', 'rank_name', 'grade', 'decree_number', 'decree_date',
        'start_date', 'end_date', 'decree_file_id', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'decree_date' => 'immutable_date',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Rank, $this>
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function decreeFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'decree_file_id');
    }
}
