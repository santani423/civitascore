<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\Employee;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Database\Factories\RankFactory;

/**
 * Master pangkat/golongan (mis. "Penata Muda" — III/a).
 *
 * @property string $id
 * @property string $university_id
 * @property string $name
 * @property string $grade
 * @property int $level
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Rank extends Model implements ScopesToInstitution
{
    /** @use HasFactory<RankFactory> */
    use Auditable, HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'name', 'grade', 'level', 'is_active'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    protected static function newFactory(): RankFactory
    {
        return RankFactory::new();
    }
}
