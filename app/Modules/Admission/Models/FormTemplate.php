<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\TemplateStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property int $version
 * @property TemplateStatus $status
 * @property array<mixed> $core_field_rules
 * @property string|null $based_on_id
 * @property CarbonImmutable|null $published_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, FormField> $fields
 */
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
