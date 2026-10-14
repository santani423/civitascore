<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\CheckEvaluation;
use Modules\Admission\Enums\CheckStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $re_registration_id
 * @property string $check_code
 * @property CheckEvaluation $evaluation
 * @property CheckStatus $status
 * @property string|null $note
 * @property string|null $checked_by
 * @property CarbonImmutable|null $checked_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ReRegistration $reRegistration
 */
class ReRegistrationCheck extends PmbModel
{
    protected $table = 'pmb_re_registration_checks';

    protected function casts(): array
    {
        return [
            'evaluation' => CheckEvaluation::class,
            'status' => CheckStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReRegistration, $this>
     */
    public function reRegistration(): BelongsTo
    {
        return $this->belongsTo(ReRegistration::class, 're_registration_id');
    }
}
