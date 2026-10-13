<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\ReRegistrationStatus;

/** Daftar ulang (SM-10). Batas efektif = extended_until ?? deadline_at (BR-34). */
class ReRegistration extends PmbModel
{
    protected $table = 'pmb_re_registrations';

    protected function casts(): array
    {
        return [
            'status' => ReRegistrationStatus::class,
            'deadline_at' => 'datetime',
            'extended_until' => 'datetime',
            'revision_targets' => 'array',
            'revision_due_at' => 'datetime',
            'declarations' => 'array',
            'confirmed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'declined_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function effectiveDeadline(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->extended_until ?? $this->deadline_at);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<AdmissionDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(AdmissionDecision::class, 'decision_id');
    }

    /**
     * @return HasMany<ReRegistrationCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(ReRegistrationCheck::class, 're_registration_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
