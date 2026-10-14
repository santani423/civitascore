<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $schedule_id
 * @property string $selection_component_id
 * @property string|null $file_upload_id
 * @property array<mixed> $valid_rows
 * @property array<mixed> $errors
 * @property string $fingerprint
 * @property string $status
 * @property string|null $created_by
 * @property CarbonImmutable|null $committed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamSchedule $schedule
 * @property-read SelectionComponent $component
 */
class ScoreImport extends PmbModel
{
    protected $table = 'pmb_score_imports';

    protected function casts(): array
    {
        return [
            'valid_rows' => 'array',
            'errors' => 'array',
            'committed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExamSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo<SelectionComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }
}
