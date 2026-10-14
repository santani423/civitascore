<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data identitas & PDDikti mahasiswa (1:1 dengan students) yang dikumpulkan
 * PMB saat daftar ulang (docs/pmb/10 §6, GAP-04). Kolom sensitif terenkripsi.
 */
class StudentProfile extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'nik', 'nationality', 'passport_number', 'religion', 'mother_name', 'father_name',
        'previous_school', 'previous_school_npsn', 'nisn', 'region_code', 'address_line', 'village', 'district', 'city',
        'province', 'postal_code', 'pddikti_extra', 'guardians',
    ];

    /** @var array<int, string> */
    protected $hidden = ['nik', 'passport_number', 'mother_name'];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'passport_number' => 'encrypted',
            'mother_name' => 'encrypted',
            'pddikti_extra' => 'encrypted:array',
            'guardians' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
