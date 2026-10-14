<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\Lifecycle;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property Lifecycle $lifecycle
 * @property string|null $from_status
 * @property string $to_status
 * @property string $actor_type
 * @property string|null $actor_id
 * @property string|null $reason_code
 * @property string|null $reason_note
 * @property array<mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property-read Application $application
 */
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
