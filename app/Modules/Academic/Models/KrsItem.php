<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academic\Database\Factories\KrsItemFactory;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property string $class_section_id
 * @property string $academic_term_id
 * @property KrsItemStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read ClassSection $classSection
 * @property-read AcademicTerm $academicTerm
 * @property-read Grade|null $grade
 */
class KrsItem extends Model implements ScopesToInstitution
{
    /** @use HasFactory<KrsItemFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'class_section_id', 'academic_term_id', 'status'];

    protected function casts(): array
    {
        return ['status' => KrsItemStatus::class];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<ClassSection, $this>
     */
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return HasOne<Grade, $this>
     */
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    protected static function newFactory(): KrsItemFactory
    {
        return KrsItemFactory::new();
    }
}
