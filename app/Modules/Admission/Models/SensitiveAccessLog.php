<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $user_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $field_or_document
 * @property string $purpose
 * @property string|null $ip_address
 * @property CarbonImmutable|null $created_at
 * @property-read User $user
 */
class SensitiveAccessLog extends PmbModel
{
    protected $table = 'pmb_sensitive_access_logs';

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
