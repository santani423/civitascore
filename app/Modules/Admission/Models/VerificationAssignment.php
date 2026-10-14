<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\AssignmentStatus;

/** Klaim/penugasan pemeriksa — satu penugasan aktif per aplikasi (FR-VER-04).
 *
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $reviewer_user_id
 * @property string|null $assigned_by
 * @property string $mode
 * @property AssignmentStatus $status
 * @property CarbonImmutable|null $claimed_until
 * @property string|null $active_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read User $reviewer
 */
class VerificationAssignment extends PmbModel
{
    protected $table = 'pmb_verification_assignments';

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $assignment->active_key = $assignment->status === AssignmentStatus::Active ? $assignment->application_id : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'claimed_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
