<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $form_field_id
 * @property string|null $value_text
 * @property array<mixed>|null $value_json
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FormField $field
 */
class ApplicationAnswer extends PmbModel
{
    protected $table = 'pmb_application_answers';

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
