<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\LecturerActivityType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;
use Modules\HumanResource\Models\Concerns\GuardsEmployeeFiles;

/**
 * Penelitian & pengabdian dosen (catatan riwayat — belum ada modul
 * Penelitian tersendiri di sistem).
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property LecturerActivityType $type
 * @property string $title
 * @property string|null $role
 * @property int $year
 * @property string|null $funding_source
 * @property string|null $amount
 * @property string|null $description
 * @property string|null $document_file_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FileUpload|null $documentFile
 */
class LecturerActivity extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use Auditable, BelongsToEmployee, GuardsEmployeeFiles, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'type', 'title', 'role', 'year', 'funding_source', 'amount',
        'description', 'document_file_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => LecturerActivityType::class,
            'year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function documentFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'document_file_id');
    }
}
