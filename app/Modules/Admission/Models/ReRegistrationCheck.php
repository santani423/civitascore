<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\CheckEvaluation;
use Modules\Admission\Enums\CheckStatus;

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
