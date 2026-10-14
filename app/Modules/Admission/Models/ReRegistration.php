<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\ReRegistrationStatus;

/** Daftar ulang (SM-10). Batas efektif = extended_until ?? deadline_at (BR-34).
 *
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $decision_id
 * @property ReRegistrationStatus $status
 * @property CarbonImmutable $deadline_at
 * @property CarbonImmutable|null $extended_until
 * @property string|null $extension_reason
 * @property string|null $extended_by
 * @property array<mixed>|null $revision_targets
 * @property CarbonImmutable|null $revision_due_at
 * @property array<mixed>|null $declarations
 * @property string|null $almamater_size
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $enrolled_at
 * @property CarbonImmutable|null $declined_at
 * @property CarbonImmutable|null $expired_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $decline_reason
 * @property string|null $approved_by
 * @property int $lock_version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read AdmissionDecision $decision
 * @property-read Collection<int, ReRegistrationCheck> $checks
 * @property-read User|null $approver
 */
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
