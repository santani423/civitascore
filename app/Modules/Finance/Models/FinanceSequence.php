<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Penomoran dokumen ber-lock (FR-CFG-04). */
class FinanceSequence extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'scope', 'key', 'next_value'];

    protected function casts(): array
    {
        return [
            'next_value' => 'integer',
        ];
    }
}
