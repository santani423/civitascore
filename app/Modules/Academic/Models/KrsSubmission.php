<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\KrsSubmissionFactory;
use Modules\Academic\Enums\KrsSubmissionStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property string $academic_term_id
 * @property KrsSubmissionStatus $status
 * @property int $total_credits
 * @property int|null $max_credits
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $decided_at
 * @property string|null $decided_by
 * @property string|null $decision_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read AcademicTerm $academicTerm
 * @property-read User|null $decider
 * @property-read Collection<int, KrsItem> $items
 */
class KrsSubmission extends Model implements ScopesToInstitution
{
    /** @use HasFactory<KrsSubmissionFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'academic_term_id', 'status', 'total_credits', 'max_credits',
        'submitted_at', 'decided_at', 'decided_by', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => KrsSubmissionStatus::class,
            'total_credits' => 'integer',
            'max_credits' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
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
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Baris KRS yang diisi lewat pengajuan ini (bukan yang didaftarkan
     * langsung oleh Bagian Akademik).
     *
     * @return HasMany<KrsItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KrsItem::class);
    }

    protected static function newFactory(): KrsSubmissionFactory
    {
        return KrsSubmissionFactory::new();
    }
}
