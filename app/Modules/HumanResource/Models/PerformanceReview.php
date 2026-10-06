<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Employee;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Enums\PerformanceCategory;
use Modules\HumanResource\Enums\PerformanceStatus;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * Penilaian kinerja sederhana: satu nilai akhir per pegawai per periode.
 * Sengaja belum memecah ke indikator/bobot — bisa ditambahkan sebagai
 * tabel anak tanpa mengubah tabel ini.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string|null $reviewer_employee_id
 * @property string $period
 * @property string $score
 * @property PerformanceCategory $category
 * @property string|null $notes
 * @property PerformanceStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employee|null $reviewer
 */
class PerformanceReview extends Model implements ScopesToInstitution
{
    use Auditable, BelongsToEmployee, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'reviewer_employee_id', 'period', 'score', 'category', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'category' => PerformanceCategory::class,
            'status' => PerformanceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id')->withTrashed();
    }
}
