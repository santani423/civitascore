<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\PaymentTiming;
use Modules\Admission\Enums\QuotaMode;

class WavePath extends PmbModel
{
    protected $table = 'pmb_wave_paths';

    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
            'payment_timing' => PaymentTiming::class,
            'max_choices' => 'integer',
            'allow_cross_system_choice' => 'boolean',
            'quota_mode' => QuotaMode::class,
            'bulk_verifiable' => 'boolean',
            'is_open' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Wave, $this>
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class, 'wave_id');
    }

    /**
     * @return BelongsTo<AdmissionPath, $this>
     */
    public function admissionPath(): BelongsTo
    {
        return $this->belongsTo(AdmissionPath::class, 'admission_path_id');
    }

    /**
     * @return BelongsTo<FormTemplate, $this>
     */
    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    /**
     * @return BelongsTo<SelectionScheme, $this>
     */
    public function selectionScheme(): BelongsTo
    {
        return $this->belongsTo(SelectionScheme::class, 'selection_scheme_id');
    }

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class, 'wave_path_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'wave_path_id');
    }
}
