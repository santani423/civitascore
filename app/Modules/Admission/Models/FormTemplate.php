<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\TemplateStatus;

class FormTemplate extends PmbModel
{
    protected $table = 'pmb_form_templates';

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'core_field_rules' => 'array',
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_template_id')->orderBy('sort_order');
    }
}
