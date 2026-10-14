<?php

namespace Modules\Admission\Services\Privacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\ApplicantProfile;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\DocumentVersion;
use Modules\Admission\Support\PmbSettings;
use Modules\FileManagement\Models\FileUpload;

/**
 * Retensi & pemusnahan (BR-41, 13 §B.7, FR-DOC-07): akun pendaftar yang
 * tidak menjadi mahasiswa dan seluruh periodenya sudah ditutup lebih lama
 * dari masa retensi → dianonimkan, berkas dokumen dihapus permanen. Data
 * keuangan (tagihan/transaksi) TIDAK dihapus — ikut retensi dokumen keuangan.
 * Juga menyediakan "unduh data saya" (hak akses subjek data, POR-12).
 */
class RetentionService
{
    public function __construct(private readonly PmbSettings $settings) {}

    /** @return array{anonymized: int, files_deleted: int} */
    public function run(): array
    {
        $years = max(1, $this->settings->int('pmb.retention_years_applicant'));
        $cutoff = now()->subYears($years);
        $anonymized = 0;
        $filesDeleted = 0;

        ApplicantAccount::query()->whereNull('anonymized_at')
            ->whereDoesntHave('applications', fn ($q) => $q->whereHas('reRegistration', fn ($r) => $r->where('status', 'enrolled')))
            ->where(fn ($q) => $q
                ->whereDoesntHave('applications', fn ($a) => $a->whereHas('period', fn ($p) => $p->whereNotIn('status', ['closed', 'archived'])->orWhere('closed_at', '>', $cutoff)))
                ->where('created_at', '<', $cutoff))
            ->chunkById(100, function ($accounts) use (&$anonymized, &$filesDeleted): void {
                foreach ($accounts as $account) {
                    $filesDeleted += $this->anonymize($account);
                    $anonymized++;
                }
            });

        return ['anonymized' => $anonymized, 'files_deleted' => $filesDeleted];
    }

    public function anonymize(ApplicantAccount $account): int
    {
        return DB::transaction(function () use ($account): int {
            $deleted = 0;
            $applicationIds = Application::query()->where('applicant_account_id', $account->getKey())->pluck('id');

            $fileIds = DocumentVersion::query()->whereHas('document', fn ($q) => $q->whereIn('application_id', $applicationIds))->pluck('file_upload_id');

            foreach (FileUpload::query()->whereIn('id', $fileIds)->get() as $file) {
                Storage::disk($file->disk)->delete($file->path);
                $file->forceFill(['original_name' => 'dihapus-retensi', 'checksum' => null])->save();
                $file->delete();
                $deleted++;
            }

            ApplicantProfile::query()->where('applicant_account_id', $account->getKey())->update([
                'full_name' => null, 'nik' => null, 'passport_number' => null, 'mother_name' => null, 'birth_place' => null,
                'address_line' => null, 'rt' => null, 'rw' => null, 'village' => null, 'district' => null, 'postal_code' => null,
                'special_needs' => null, 'special_needs_note' => null, 'pddikti_extra' => null, 'identity_hash' => null,
            ]);
            $account->guardians()->delete();
            $account->achievements()->update(['name' => 'dihapus', 'organizer' => null]);
            Application::query()->whereIn('id', $applicationIds)->update(['profile_snapshot' => null, 'kip_number' => null, 'referral_code' => null]);
            DB::table('pmb_application_answers')->whereIn('application_id', $applicationIds)->update(['value_text' => null, 'value_json' => null]);

            $account->tokens()->delete();
            $account->forceFill([
                'name' => 'Anonim',
                'email' => 'anon-'.Str::lower((string) $account->getKey()).'@anonymized.invalid',
                'phone' => null,
                'password' => Str::password(40),
                'status' => AccountStatus::Deactivated,
                'deactivated_at' => $account->deactivated_at ?? now(),
                'anonymized_at' => now(),
            ])->save();

            return $deleted;
        });
    }

    /**
     * Hak akses subjek data: seluruh data pribadi milik akun dalam JSON.
     *
     * @return array<string, mixed>
     */
    public function exportFor(ApplicantAccount $account): array
    {
        $account->load(['profile', 'guardians', 'educations.reportScores', 'achievements', 'consents']);
        $profile = $account->profile;

        return [
            'generated_at' => now()->toIso8601String(),
            'account' => $account->only(['name', 'email', 'phone', 'status', 'created_at', 'email_verified_at']),
            'profile' => $profile ? [...$profile->attributesToArray(), 'nik' => $profile->nik, 'passport_number' => $profile->passport_number, 'mother_name' => $profile->mother_name, 'special_needs' => $profile->special_needs] : null,
            'guardians' => $account->guardians->map(fn ($g) => [...$g->attributesToArray(), 'nik' => $g->nik])->all(),
            'educations' => $account->educations->toArray(),
            'achievements' => $account->achievements->toArray(),
            'consents' => $account->consents->map->only(['consent_type', 'document_version', 'granted_at', 'revoked_at'])->all(),
            'applications' => Application::query()->where('applicant_account_id', $account->getKey())->with(['choices.studyProgram', 'answers.field', 'currentDecision'])->get()
                ->map(fn (Application $a) => [
                    'registration_number' => $a->registration_number,
                    'form_status' => $a->form_status->value,
                    'submitted_at' => $a->submitted_at?->toIso8601String(),
                    'choices' => $a->choices->map(fn ($c) => $c->studyProgram?->name)->all(),
                    'answers' => $a->answers->map(fn ($x) => ['field' => $x->field?->label, 'value' => $x->value_json ?? ($x->field?->is_sensitive && $x->value_text !== null ? rescue(fn () => decrypt($x->value_text), '', false) : $x->value_text)])->all(),
                    'decision' => $a->currentDecision?->publication_status?->value === 'published' ? $a->currentDecision->decision->value : null,
                ])->all(),
        ];
    }
}
