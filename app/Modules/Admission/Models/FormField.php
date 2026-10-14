<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DocumentStage;

/**
 * @property string $id
 * @property string $university_id
 * @property string $form_template_id
 * @property string $section
 * @property string $key
 * @property string $label
 * @property string|null $help_text
 * @property string|null $placeholder
 * @property string $type
 * @property array<mixed>|null $options
 * @property bool $is_required
 * @property array<mixed>|null $validation
 * @property array<mixed>|null $visibility_rule
 * @property bool $is_sensitive
 * @property bool $is_reportable
 * @property DocumentStage $stage
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FormTemplate $template
 */
class FormField extends PmbModel
{
    protected $table = 'pmb_form_fields';

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'validation' => 'array',
            'visibility_rule' => 'array',
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_reportable' => 'boolean',
            'stage' => DocumentStage::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FormTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }
}
