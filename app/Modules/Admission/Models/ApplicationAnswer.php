<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
