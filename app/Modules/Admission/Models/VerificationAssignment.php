<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\AssignmentStatus;

/** Klaim/penugasan pemeriksa — satu penugasan aktif per aplikasi (FR-VER-04). */
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
