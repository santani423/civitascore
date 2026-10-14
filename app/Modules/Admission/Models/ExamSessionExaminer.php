<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ExaminerRole;

/**
 * @property string $id
 * @property string $university_id
 * @property string $session_id
 * @property string $user_id
 * @property ExaminerRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamSession $session
 * @property-read User $user
 */
class ExamSessionExaminer extends PmbModel
{
    protected $table = 'pmb_exam_session_examiners';

    protected function casts(): array
    {
        return [
            'role' => ExaminerRole::class,
        ];
    }

    /**
     * @return BelongsTo<ExamSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
