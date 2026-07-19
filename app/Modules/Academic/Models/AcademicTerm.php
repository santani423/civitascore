<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\AcademicTermFactory;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $academic_year
 * @property AcademicSemester $semester
 * @property bool $is_current
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class AcademicTerm extends Model implements ScopesToInstitution
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'academic_year', 'semester', 'is_current', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'semester' => AcademicSemester::class,
            'is_current' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function label(): string
    {
        $semesterLabel = $this->semester === AcademicSemester::Ganjil ? 'Ganjil' : 'Genap';

        return "{$this->academic_year} {$semesterLabel}";
    }

    protected static function newFactory(): AcademicTermFactory
    {
        return AcademicTermFactory::new();
    }
}
