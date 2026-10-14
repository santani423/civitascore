<?php

namespace Modules\Admission\Services\Account;

use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\DuplicateFlagStatus;
use Modules\Admission\Enums\DuplicateFlagType;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\ApplicantAchievement;
use Modules\Admission\Models\ApplicantEducation;
use Modules\Admission\Models\ApplicantGuardian;
use Modules\Admission\Models\ApplicantProfile;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\DuplicateFlag;
use Modules\Admission\Models\ReportScore;
use Modules\Admission\Support\IdentityHasher;
use Modules\Admission\Support\PmbSettings;

/**
 * Profil identitas, pendidikan, rapor, orang tua/wali, prestasi (04 §C.1,
 * §D.2). Profil melekat ke akun; saat kirim formulir di-snapshot ke aplikasi.
 * Identitas dikunci setelah ada aplikasi terkirim (BR-12 / FR-ACC-05).
 */
class ProfileService
{
    /** Field identitas yang terkunci setelah ada aplikasi terkirim. */
    public const LOCKED_IDENTITY_FIELDS = ['full_name', 'nationality', 'nik', 'passport_number', 'passport_country', 'birth_date', 'birth_place'];

    private const PROFILE_FIELDS = [
        'full_name', 'nationality', 'nik', 'passport_number', 'passport_country', 'birth_place', 'birth_date', 'gender',
        'religion', 'mother_name', 'address_line', 'rt', 'rw', 'village', 'district', 'city', 'province', 'region_code',
        'postal_code', 'special_needs', 'special_needs_note', 'pddikti_extra',
    ];

    public function __construct(private readonly PmbSettings $settings) {}

    public function profileFor(ApplicantAccount $account): ApplicantProfile
    {
        return ApplicantProfile::query()->firstOrCreate(
            ['applicant_account_id' => $account->getKey()],
            ['university_id' => $account->university_id, 'full_name' => $account->name, 'nationality' => 'ID'],
        );
    }

    public function identityLocked(ApplicantAccount $account): bool
    {
        return Application::query()->where('applicant_account_id', $account->getKey())->whereNotNull('first_submitted_at')
            ->whereNotIn('form_status', [FormStatus::Withdrawn->value, FormStatus::Void->value])->exists();
    }

    /**
     * Simpan profil (parsial). NIK/paspor → identity_hash HMAC; bentrok →
     * 409 IDENTITY_ALREADY_REGISTERED dengan pesan netral (AC-02).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveProfile(ApplicantAccount $account, array $data, ?int $lockVersion, bool $byStaff = false): ApplicantProfile
    {
        $profile = $this->profileFor($account);

        if ($lockVersion !== null && $lockVersion !== $profile->lock_version) {
            throw new DomainRuleException('Data telah diubah di tempat lain. Muat ulang halaman.', 'VERSION_CONFLICT', 409, ['current_version' => $profile->lock_version]);
        }

        $values = Arr::only($data, self::PROFILE_FIELDS);

        if (! $byStaff && $this->identityLocked($account)) {
            foreach (self::LOCKED_IDENTITY_FIELDS as $field) {
                if (array_key_exists($field, $values) && (string) $values[$field] !== (string) $this->currentValue($profile, $field)) {
                    throw new DomainRuleException('Data identitas terkunci karena sudah ada pendaftaran yang dikirim. Ajukan koreksi kepada panitia.', 'IDENTITY_LOCKED', 423);
                }
            }
        }

        $values = $this->normalizeIdentity($values, $profile);
        $this->validateBirthDate($values['birth_date'] ?? null);

        $nationality = strtoupper((string) ($values['nationality'] ?? $profile->nationality ?? 'ID'));
        $nik = array_key_exists('nik', $values) ? $values['nik'] : $profile->nik;
        $passport = array_key_exists('passport_number', $values) ? $values['passport_number'] : $profile->passport_number;
        $passportCountry = array_key_exists('passport_country', $values) ? $values['passport_country'] : $profile->passport_country;

        $identityHash = match (true) {
            $nationality === 'ID' && $nik !== null && $nik !== '' => IdentityHasher::forNationalId((string) $account->university_id, (string) $nik),
            $nationality !== 'ID' && $passport && $passportCountry => IdentityHasher::forPassport((string) $account->university_id, (string) $passportCountry, (string) $passport),
            default => null,
        };

        if ($identityHash !== null) {
            $owner = ApplicantProfile::query()->where('identity_hash', $identityHash)->where('applicant_account_id', '!=', $account->getKey())->first();

            if ($owner !== null) {
                throw new DomainRuleException('Identitas ini sudah terdaftar pada akun lain. Silakan masuk dengan akun tersebut atau hubungi panitia.', 'IDENTITY_ALREADY_REGISTERED', 409, ['can_claim' => true]);
            }
        }

        try {
            return DB::transaction(function () use ($profile, $values, $identityHash): ApplicantProfile {
                $profile->forceFill([...$values, 'identity_hash' => $identityHash, 'lock_version' => $profile->lock_version + 1])->save();

                return $profile->refresh();
            });
        } catch (UniqueConstraintViolationException) {
            throw new DomainRuleException('Identitas ini sudah terdaftar pada akun lain. Silakan masuk dengan akun tersebut atau hubungi panitia.', 'IDENTITY_ALREADY_REGISTERED', 409, ['can_claim' => true]);
        }
    }

    /**
     * "Klaim identitas" (04 §C.3): pendaftar yang bentrok identitas meminta
     * petugas memverifikasi — tiket pmb_duplicate_flags tipe identity_claim.
     */
    public function claimIdentity(ApplicantAccount $account, string $nationalId, ?string $note): DuplicateFlag
    {
        $hash = IdentityHasher::forNationalId((string) $account->university_id, $nationalId);
        $owner = ApplicantProfile::query()->where('identity_hash', $hash)->first();

        return DuplicateFlag::query()->create([
            'applicant_account_id' => $account->getKey(),
            'related_account_id' => $owner?->applicant_account_id,
            'type' => DuplicateFlagType::IdentityClaim,
            'status' => DuplicateFlagStatus::Open,
            'note' => $note ? mb_substr($note, 0, 1000) : null,
        ]);
    }

    // ------------------------------------------------------------------ Pendidikan

    /** @param  array<string, mixed>  $data */
    public function saveEducation(ApplicantAccount $account, ?ApplicantEducation $education, array $data): ApplicantEducation
    {
        if ($education !== null && $this->educationLocked($education)) {
            throw new DomainRuleException('Riwayat pendidikan dirujuk pendaftaran yang sudah dikirim.', 'EDUCATION_LOCKED', 423);
        }

        $values = Arr::only($data, ['level', 'institution_name', 'institution_npsn', 'school_type', 'major', 'graduation_year', 'is_graduating', 'nisn', 'previous_student_id', 'gpa', 'credits_earned', 'previous_accreditation', 'city', 'province', 'country']);

        if (isset($values['graduation_year'])) {
            $values['is_graduating'] = (int) $values['graduation_year'] >= (int) now()->format('Y') && ($values['is_graduating'] ?? true);
        }

        if ($education === null) {
            return ApplicantEducation::query()->create([...$values, 'applicant_account_id' => $account->getKey()]);
        }

        $education->forceFill($values)->save();

        return $education->refresh();
    }

    public function deleteEducation(ApplicantEducation $education): void
    {
        if ($this->educationLocked($education) || Application::query()->where('primary_education_id', $education->getKey())->exists()) {
            throw new DomainRuleException('Riwayat pendidikan masih dirujuk pendaftaran.', 'EDUCATION_LOCKED', 423);
        }

        $education->delete();
    }

    /**
     * Ganti seluruh set nilai rapor (11 §A.4 `PUT report-scores`).
     *
     * @param  array<int, array{semester: int, subject_code: string, subject_name?: string, score: float|int|string}>  $scores
     */
    public function replaceReportScores(ApplicantEducation $education, array $scores): void
    {
        if ($this->educationLocked($education)) {
            throw new DomainRuleException('Nilai rapor dirujuk pendaftaran yang sudah dikirim.', 'EDUCATION_LOCKED', 423);
        }

        $seen = [];

        foreach ($scores as $row) {
            $key = $row['semester'].'|'.strtoupper($row['subject_code']);

            if (isset($seen[$key])) {
                throw ValidationException::withMessages(['scores' => "Nilai semester {$row['semester']} mapel {$row['subject_code']} ganda."]);
            }

            $seen[$key] = true;
        }

        DB::transaction(function () use ($education, $scores): void {
            ReportScore::query()->where('applicant_education_id', $education->getKey())->delete();

            foreach ($scores as $row) {
                ReportScore::query()->create([
                    'applicant_education_id' => $education->getKey(),
                    'semester' => (int) $row['semester'],
                    'subject_code' => strtoupper((string) $row['subject_code']),
                    'subject_name' => (string) ($row['subject_name'] ?? $row['subject_code']),
                    'score' => (float) $row['score'],
                ]);
            }
        });
    }

    // ------------------------------------------------------------------ Orang tua/wali & prestasi

    /** @param  array<string, mixed>  $data */
    public function saveGuardian(ApplicantAccount $account, ?ApplicantGuardian $guardian, array $data): ApplicantGuardian
    {
        $values = Arr::only($data, ['relationship', 'full_name', 'phone', 'email', 'nik', 'birth_date', 'education_level', 'occupation', 'income_range', 'is_deceased']);

        if ($guardian === null) {
            return ApplicantGuardian::query()->create([...$values, 'applicant_account_id' => $account->getKey()]);
        }

        $guardian->forceFill($values)->save();

        return $guardian->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function saveAchievement(ApplicantAccount $account, ?ApplicantAchievement $achievement, array $data): ApplicantAchievement
    {
        if ($achievement !== null && $this->achievementLocked($achievement)) {
            throw new DomainRuleException('Prestasi sudah diverifikasi pada pendaftaran yang dikirim.', 'ACHIEVEMENT_LOCKED', 423);
        }

        $values = Arr::only($data, ['name', 'category', 'level', 'rank', 'year', 'organizer']);

        if ($achievement === null) {
            return ApplicantAchievement::query()->create([...$values, 'applicant_account_id' => $account->getKey()]);
        }

        $achievement->forceFill($values)->save();

        return $achievement->refresh();
    }

    public function deleteAchievement(ApplicantAchievement $achievement): void
    {
        if ($this->achievementLocked($achievement)) {
            throw new DomainRuleException('Prestasi sudah diverifikasi pada pendaftaran yang dikirim.', 'ACHIEVEMENT_LOCKED', 423);
        }

        $achievement->delete();
    }

    /** Usia (tahun penuh) pada tanggal acuan, mis. awal periode. */
    public static function ageOn(?CarbonImmutable $birthDate, CarbonImmutable $reference): ?int
    {
        return $birthDate === null ? null : (int) floor($birthDate->diffInYears($reference));
    }

    private function educationLocked(ApplicantEducation $education): bool
    {
        return Application::query()->where('primary_education_id', $education->getKey())->whereNotNull('submitted_at')
            ->whereIn('form_status', [FormStatus::Submitted->value, FormStatus::Locked->value])->exists();
    }

    private function achievementLocked(ApplicantAchievement $achievement): bool
    {
        return $achievement->application_document_id !== null
            && Application::query()->where('applicant_account_id', $achievement->applicant_account_id)
                ->whereIn('form_status', [FormStatus::Submitted->value, FormStatus::Locked->value])->exists();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeIdentity(array $values, ApplicantProfile $profile): array
    {
        if (isset($values['full_name'])) {
            $name = trim(preg_replace('/\s+/', ' ', (string) $values['full_name']) ?? '');

            if (! preg_match("/^[\p{L}\s'.,\-]+$/u", $name)) {
                throw ValidationException::withMessages(['full_name' => 'Nama hanya boleh berisi huruf, spasi, dan tanda \' . , -']);
            }

            // 04 §D.2.1: tidak boleh seluruhnya huruf kapital (tulis sesuai ijazah).
            if (mb_strlen($name) > 3 && mb_strtoupper($name) === $name) {
                throw ValidationException::withMessages(['full_name' => 'Tulis nama sesuai ijazah dengan huruf besar-kecil, bukan seluruhnya kapital.']);
            }

            $values['full_name'] = $name;
        }

        if (isset($values['nationality'])) {
            $values['nationality'] = strtoupper((string) $values['nationality']);
        }

        if (array_key_exists('nik', $values) && $values['nik'] !== null && $values['nik'] !== '') {
            $nik = preg_replace('/\D/', '', (string) $values['nik']) ?? '';

            if (strlen($nik) !== 16) {
                throw ValidationException::withMessages(['nik' => 'NIK harus 16 digit angka.']);
            }

            $values['nik'] = $nik;
        }

        if (isset($values['passport_country'])) {
            $values['passport_country'] = strtoupper((string) $values['passport_country']);
        }

        return $values;
    }

    private function validateBirthDate(?string $birthDate): void
    {
        if ($birthDate === null) {
            return;
        }

        $age = CarbonImmutable::parse($birthDate)->diffInYears(CarbonImmutable::now());
        $min = $this->settings->int('pmb.min_age');
        $max = $this->settings->int('pmb.max_age');

        if ($age < $min || $age > $max) {
            throw ValidationException::withMessages(['birth_date' => "Usia pendaftar harus {$min}–{$max} tahun."]);
        }
    }

    private function currentValue(ApplicantProfile $profile, string $field): mixed
    {
        $value = $profile->{$field};

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    /**
     * Peringatan konsistensi NIK ↔ tanggal lahir (digit 7–12, perempuan +40) —
     * peringatan, bukan blokir (04 §D.2.1).
     */
    public static function nikBirthDateWarning(?string $nik, ?string $birthDate): ?string
    {
        if ($nik === null || strlen($nik) !== 16 || $birthDate === null) {
            return null;
        }

        $day = (int) substr($nik, 6, 2);
        $month = (int) substr($nik, 8, 2);
        $year = (int) substr($nik, 10, 2);
        $date = CarbonImmutable::parse($birthDate);

        if ($day > 40) {
            $day -= 40;
        }

        return ($day === (int) $date->format('d') && $month === (int) $date->format('m') && $year === (int) $date->format('y'))
            ? null
            : 'Tanggal lahir tidak sesuai dengan digit NIK. Periksa kembali.';
    }
}
