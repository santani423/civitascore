<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;

/** Penetapan golongan UKT per mahasiswa (riwayat, BR-KEU-16). */
class StudentUktAssignment extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'ukt_group_id', 'effective_from_term_id', 'basis', 'decree_number', 'status', 'assigned_by', 'superseded_at'];

    protected function casts(): array
    {
        return [
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<UktGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(UktGroup::class, 'ukt_group_id');
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function effectiveFromTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'effective_from_term_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
