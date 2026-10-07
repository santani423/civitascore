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
use Modules\HumanResource\Database\Factories\PositionFactory;
use Modules\HumanResource\Enums\PositionType;

/**
 * Master jabatan (struktural maupun fungsional).
 *
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property PositionType $type
 * @property string|null $description
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Position extends Model implements ScopesToInstitution
{
    /** @use HasFactory<PositionFactory> */
    use Auditable, HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'type', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => PositionType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    protected static function newFactory(): PositionFactory
    {
        return PositionFactory::new();
    }
}
