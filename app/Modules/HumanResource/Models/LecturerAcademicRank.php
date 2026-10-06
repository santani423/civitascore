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
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * Riwayat jabatan akademik dosen (Asisten Ahli → Lektor → Lektor Kepala →
 * Profesor). Jabatan aktif juga disalin ke lecturers.academic_rank.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property AcademicRank $academic_rank
 * @property string|null $credit_points
 * @property string|null $decree_number
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $decree_file_id
 * @property bool $is_current
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $decreeFile
 */
class LecturerAcademicRank extends Model implements ScopesToInstitution
{
    use Auditable, BelongsToEmployee, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'academic_rank', 'credit_points', 'decree_number', 'start_date',
        'end_date', 'decree_file_id', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'academic_rank' => AcademicRank::class,
            'credit_points' => 'decimal:2',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function decreeFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'decree_file_id');
    }
}
