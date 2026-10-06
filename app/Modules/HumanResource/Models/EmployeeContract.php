<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Database\Factories\EmployeeContractFactory;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property string $contract_number
 * @property ContractType $contract_type
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property ContractStatus $status
 * @property string|null $document_file_id
 * @property string|null $notes
 * @property int|null $last_reminder_days
 * @property CarbonImmutable|null $terminated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $documentFile
 */
class EmployeeContract extends Model implements ScopesToInstitution
{
    /** @use HasFactory<EmployeeContractFactory> */
    use Auditable, BelongsToEmployee, HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'contract_number', 'contract_type', 'start_date', 'end_date', 'status',
        'document_file_id', 'notes', 'last_reminder_days', 'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'contract_type' => ContractType::class,
            'status' => ContractStatus::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'last_reminder_days' => 'integer',
            'terminated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Sisa hari sampai kontrak berakhir (negatif = sudah lewat), null untuk
     * kontrak tanpa tanggal akhir (PKWTT).
     */
    public function daysRemaining(): ?int
    {
        if ($this->end_date === null) {
            return null;
        }

        return (int) CarbonImmutable::today()->diffInDays($this->end_date, false);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function documentFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'document_file_id');
    }

    protected static function newFactory(): EmployeeContractFactory
    {
        return EmployeeContractFactory::new();
    }
}
