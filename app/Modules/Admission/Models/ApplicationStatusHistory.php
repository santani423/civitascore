<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\Lifecycle;

class ApplicationStatusHistory extends PmbModel
{
    protected $table = 'pmb_application_status_histories';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'lifecycle' => Lifecycle::class,
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }
}
