<?php

namespace Modules\Admission\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Admission\Enums\AccountStatus;
use Modules\Tenancy\Models\University;

/**
 * Akun calon mahasiswa (KD-01): tenant-scoped, terpisah dari `users`
 * (email unik per tenant, bukan global). Token Sanctum ber-ability
 * `applicant`; tabel personal_access_tokens sudah polymorphic.
 */
class ApplicantAccount extends Authenticatable implements ScopesToInstitution
{
    use HasApiTokens, HasUlids, Notifiable, TenantScoped;

    public const TOKEN_ABILITY = 'applicant';

    protected $table = 'pmb_applicant_accounts';

    protected $guarded = ['id'];

    /** @var array<int, string> */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'failed_login_count' => 'integer',
            'lockout_count' => 'integer',
        ];
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->anonymized_at === null ? $this->email : null;
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return HasOne<ApplicantProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(ApplicantProfile::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<ApplicantEducation, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(ApplicantEducation::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<ApplicantGuardian, $this>
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(ApplicantGuardian::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<ApplicantAchievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(ApplicantAchievement::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<AccountConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(AccountConsent::class, 'applicant_account_id');
    }
}
