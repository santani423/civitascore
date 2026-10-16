<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;

/** Catatan tindak lanjut penagihan (FR-PTG-04). */
class CollectionNote extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'channel', 'note', 'promise_amount', 'promise_date', 'created_by'];

    protected function casts(): array
    {
        return [
            'promise_amount' => 'decimal:2',
            'promise_date' => 'date',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
