<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DocumentStage;

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
