<?php

namespace Modules\HumanResource\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Employee;

/**
 * Relasi ke master pegawai untuk seluruh tabel riwayat/administrasi SDM
 * (semuanya punya kolom employee_id).
 */
trait BelongsToEmployee
{
    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
