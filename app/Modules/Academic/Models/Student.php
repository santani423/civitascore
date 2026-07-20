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
use Modules\Academic\Database\Factories\StudentFactory;
use Modules\Academic\Enums\StudentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $study_program_id
 * @property string $nim
 * @property string $name
 * @property string|null $email
 * @property int $admission_year
 * @property StudentStatus $status
 * @property CarbonImmutable $enrolled_at
 * @property CarbonImmutable|null $graduated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StudyProgram $studyProgram
 */
class Student extends Model implements ScopesToInstitution
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'study_program_id', 'nim', 'name', 'email',
        'admission_year', 'status', 'enrolled_at', 'graduated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'admission_year' => 'integer',
            'enrolled_at' => 'date',
            'graduated_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<KrsItem, $this>
     */
    public function krsItems(): HasMany
    {
        return $this->hasMany(KrsItem::class);
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }
}
