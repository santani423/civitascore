<?php

namespace Modules\Academic\Models;

use App\Models\User;
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
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Models\Invoice;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string $university_id
 * @property string $study_program_id
 * @property string|null $academic_advisor_id
 * @property string $nim
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $photo_file_id
 * @property CarbonImmutable|null $tanggal_lahir
 * @property string|null $birth_place
 * @property string|null $gender
 * @property int $admission_year
 * @property StudentStatus $status
 * @property CarbonImmutable $enrolled_at
 * @property CarbonImmutable|null $graduated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StudyProgram $studyProgram
 * @property-read User|null $user
 * @property-read Lecturer|null $academicAdvisor
 * @property-read FileUpload|null $photo
 */
class Student extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasUlids, TenantScoped;

    /**
     * Alasan perubahan status yang sedang disimpan — bukan kolom; dibaca
     * StudentObserver saat menulis riwayat status (mis. "Cuti disetujui").
     */
    public ?string $statusChangeReason = null;

    protected $fillable = [
        'university_id', 'study_program_id', 'academic_advisor_id', 'user_id', 'nim', 'name', 'email',
        'phone', 'address', 'photo_file_id', 'tanggal_lahir', 'birth_place', 'gender',
        'admission_year', 'status', 'enrolled_at', 'graduated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'tanggal_lahir' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Dosen wali (Dosen Pembimbing Akademik) — penyetuju KRS mahasiswa ini.
     *
     * @return BelongsTo<Lecturer, $this>
     */
    public function academicAdvisor(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'academic_advisor_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_file_id');
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

    /**
     * @return HasMany<KrsSubmission, $this>
     */
    public function krsSubmissions(): HasMany
    {
        return $this->hasMany(KrsSubmission::class);
    }

    /**
     * @return HasMany<StudentStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    /**
     * @return HasMany<StudentRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(StudentRequest::class);
    }

    /** Foto profil: mahasiswa itu sendiri atau pengguna yang boleh membaca data mahasiswa. */
    public function allowsFileAccess(User $user): bool
    {
        return $this->user_id === $user->id || $user->hasPermissionTo('students.read');
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }
}
