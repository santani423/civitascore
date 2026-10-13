<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\RegistrationPaymentStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Enums\VerificationStatus;

/**
 * Satu pendaftaran oleh satu akun pada satu gelombang-jalur. Status per
 * siklus hidup disimpan di kolom terpisah (KD-02); `current_stage` untuk
 * pendaftar diturunkan oleh ApplicationStageResolver.
 */
class Application extends PmbModel
{
    protected $table = 'pmb_applications';

    /** @var array<int, string> */
    protected $hidden = ['profile_snapshot', 'kip_number', 'active_key'];

    protected static function booted(): void
    {
        // Satu aplikasi aktif per (akun, gelombang-jalur): NULL saat void/withdrawn (10 §5).
        static::saving(function (self $application): void {
            $application->active_key = $application->form_status?->isActive() ? $application->wave_path_id : null;
        });
    }

    protected function casts(): array
    {
        return [
            'form_status' => FormStatus::class,
            'verification_status' => VerificationStatus::class,
            'selection_status' => SelectionStatus::class,
            'registration_payment_status' => RegistrationPaymentStatus::class,
            'config_snapshot' => 'array',
            'profile_snapshot' => 'encrypted:array',
            'declarations' => 'array',
            'revision_targets' => 'array',
            'revision_due_at' => 'datetime',
            'kip_number' => 'encrypted',
            'scholarship_interest' => 'boolean',
            'submitted_at' => 'datetime',
            'first_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('form_status', [FormStatus::Void->value, FormStatus::Withdrawn->value]);
    }

    /**
     * @return BelongsTo<ApplicantAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ApplicantAccount::class, 'applicant_account_id');
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    /**
     * @return BelongsTo<Wave, $this>
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class, 'wave_id');
    }

    /**
     * @return BelongsTo<WavePath, $this>
     */
    public function wavePath(): BelongsTo
    {
        return $this->belongsTo(WavePath::class, 'wave_path_id');
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
     * @return BelongsTo<ApplicantEducation, $this>
     */
    public function primaryEducation(): BelongsTo
    {
        return $this->belongsTo(ApplicantEducation::class, 'primary_education_id');
    }

    /**
     * @return HasMany<ApplicationChoice, $this>
     */
    public function choices(): HasMany
    {
        return $this->hasMany(ApplicationChoice::class, 'application_id')->orderBy('priority');
    }

    /**
     * @return HasMany<ApplicationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class, 'application_id');
    }

    /**
     * @return HasMany<ApplicationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    /**
     * @return HasMany<PmbInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(PmbInvoice::class, 'application_id');
    }

    /**
     * @return HasMany<ApplicationStatusHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<SelectionScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(SelectionScore::class, 'application_id');
    }

    /**
     * @return HasMany<AdmissionDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(AdmissionDecision::class, 'application_id')->orderBy('decision_version');
    }

    /**
     * @return HasOne<AdmissionDecision, $this>
     */
    public function currentDecision(): HasOne
    {
        return $this->hasOne(AdmissionDecision::class, 'application_id')->where('is_current', true);
    }

    /**
     * @return HasOne<ReRegistration, $this>
     */
    public function reRegistration(): HasOne
    {
        return $this->hasOne(ReRegistration::class, 'application_id');
    }

    /**
     * @return HasOne<StudentConversion, $this>
     */
    public function conversion(): HasOne
    {
        return $this->hasOne(StudentConversion::class, 'application_id');
    }

    /**
     * @return HasOne<VerificationAssignment, $this>
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(VerificationAssignment::class, 'application_id')->where('status', 'active');
    }

    /**
     * @return HasMany<ReviewNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ReviewNote::class, 'application_id')->latest();
    }

    /**
     * @return HasMany<ExamParticipant, $this>
     */
    public function examParticipations(): HasMany
    {
        return $this->hasMany(ExamParticipant::class, 'application_id');
    }

    /**
     * @return HasMany<DuplicateFlag, $this>
     */
    public function duplicateFlags(): HasMany
    {
        return $this->hasMany(DuplicateFlag::class, 'applicant_account_id', 'applicant_account_id');
    }

    /** Pilihan prioritas pertama (untuk daftar & laporan). */
    public function firstChoice(): ?ApplicationChoice
    {
        return $this->relationLoaded('choices') ? $this->choices->first() : $this->choices()->first();
    }
}
