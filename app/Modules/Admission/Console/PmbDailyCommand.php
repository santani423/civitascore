<?php

namespace Modules\Admission\Console;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\IdempotencyKey;
use Modules\Admission\Models\OtpChallenge;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\Wave;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Services\Exam\ExamService;
use Modules\Admission\Services\Payment\InvoiceService;
use Modules\Admission\Services\Payment\ReconciliationService;
use Modules\Admission\Services\Privacy\RetentionService;
use Modules\Admission\Services\ReRegistration\ReRegistrationService;
use Modules\Admission\Services\Reporting\ReportService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Admission\Support\Actor;
use Modules\Tenancy\Models\University;
use Throwable;

/**
 * Job harian: draft tidak dikirim → void, revisi kedaluwarsa (BR-24),
 * tagihan kedaluwarsa, daftar ulang kedaluwarsa (BR-35), pengingat,
 * heuristik duplikat, rekonsiliasi harian & kuota, snapshot statistik,
 * penutupan periode, retensi bulanan (BR-41), pembersihan OTP/idempotensi.
 */
class PmbDailyCommand extends Command
{
    protected $signature = 'pmb:daily {--only= : Jalankan satu langkah saja}';

    protected $description = 'Menjalankan pekerjaan harian PMB.';

    public function handle(TenantContext $tenant): int
    {
        foreach (University::query()->where('is_active', true)->pluck('id') as $universityId) {
            $tenant->setUniversityId((string) $universityId);

            $steps = [
                'void-draft' => fn () => $this->voidUnsubmittedDrafts(),
                'pengingat-draft' => fn () => $this->remindIncompleteDrafts(),
                'revisi' => fn () => app(VerificationService::class)->expireRevisions(),
                'tagihan' => fn () => app(InvoiceService::class)->expireOverdue(),
                'daftar-ulang' => fn () => app(ReRegistrationService::class)->expireOverdue(),
                'pengingat-daftar-ulang' => fn () => app(ReRegistrationService::class)->sendDeadlineReminders(),
                'pengingat-ujian' => fn () => app(ExamService::class)->sendReminders(),
                'duplikat' => fn () => app(VerificationService::class)->detectDuplicates(),
                'rekonsiliasi' => fn () => app(ReconciliationService::class)->dailyRun(),
                'kuota' => fn () => $this->reconcileQuotas(),
                'statistik' => fn () => app(ReportService::class)->snapshotDaily(),
                'tutup-periode' => fn () => $this->closeFinishedPeriods(),
                'retensi' => fn () => now()->day === 1 ? app(RetentionService::class)->run() : 'bukan tanggal 1',
            ];

            foreach ($steps as $label => $step) {
                if ($this->option('only') && $this->option('only') !== $label) {
                    continue;
                }

                try {
                    $result = $step();
                    $this->line("[{$universityId}] {$label}: ".(is_scalar($result) ? $result : json_encode($result)));
                } catch (Throwable $e) {
                    report($e);
                    $this->error("[{$universityId}] {$label}: {$e->getMessage()}");
                }
            }
        }

        $tenant->setUniversityId(null);
        OtpChallenge::query()->withoutGlobalScopes()->where('created_at', '<', now()->subDays(7))->delete();
        IdempotencyKey::query()->where('expires_at', '<', now())->delete();

        return self::SUCCESS;
    }

    /** D.4: draft yang tidak dikirim saat gelombang tutup → void (WAVE_CLOSED). */
    private function voidUnsubmittedDrafts(): int
    {
        $count = 0;

        Application::query()->where('form_status', FormStatus::Draft->value)
            ->whereHas('wave', fn ($q) => $q->where('registration_closes_at', '<', now()))->get()
            ->each(function (Application $application) use (&$count): void {
                app(ApplicationService::class)->discardDraft($application, Actor::system(), 'WAVE_CLOSED');
                app(OutboxRecorder::class)->notifyApplicant($application->account, 'pmb.application.void', ['path_name' => $application->admissionPath?->name], aggregate: $application);
                $count++;
            });

        return $count;
    }

    /** 13 §A.2 `pmb.application.incomplete_reminder`: H-3 & H-1 tutup gelombang. */
    private function remindIncompleteDrafts(): int
    {
        $count = 0;

        foreach (Wave::query()->where('status', 'open')->get() as $wave) {
            $days = (int) floor(now()->diffInDays(CarbonImmutable::parse($wave->registration_closes_at), false));

            if (! in_array($days, [3, 1], true)) {
                continue;
            }

            Application::query()->with(['account', 'admissionPath'])->where('wave_id', $wave->getKey())->where('form_status', FormStatus::Draft->value)->get()
                ->each(function (Application $application) use ($wave, &$count): void {
                    app(OutboxRecorder::class)->notifyApplicant($application->account, 'pmb.application.incomplete_reminder', [
                        'path_name' => $application->admissionPath?->name,
                        'deadline' => $wave->registration_closes_at->toDateTimeString(),
                    ], aggregate: $application);
                    $count++;
                });
        }

        return $count;
    }

    /**
     * Aturan integritas 10 §4.11: accepted_count = keputusan accepted current
     * terbit dengan daftar ulang non-terminal-gagal; selisih → perbaiki & alert.
     */
    private function reconcileQuotas(): int
    {
        $fixed = 0;

        foreach (DB::table('pmb_quotas')->where('university_id', app(TenantContext::class)->universityId())->get() as $quota) {
            $actual = DB::table('pmb_admission_decisions')->where('quota_id', $quota->id)->where('is_current', true)->where('decision', 'accepted')->where('publication_status', 'published')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('pmb_re_registrations')->whereColumn('pmb_re_registrations.application_id', 'pmb_admission_decisions.application_id')->whereIn('pmb_re_registrations.status', ['declined', 'expired', 'cancelled']))
                ->count();

            if ($actual !== (int) $quota->accepted_count) {
                DB::table('pmb_quotas')->where('id', $quota->id)->update(['accepted_count' => $actual, 'lock_version' => $quota->lock_version + 1]);
                \Illuminate\Support\Facades\Log::warning('[pmb-quota] accepted_count direkonsiliasi', ['quota' => $quota->id, 'from' => $quota->accepted_count, 'to' => $actual]);
                $fixed++;
            }
        }

        return $fixed;
    }

    /** FR-CFG-01: periode ditutup otomatis saat semua gelombang selesai & ends_on lewat. */
    private function closeFinishedPeriods(): int
    {
        $count = 0;

        foreach (Period::query()->where('status', 'published')->where('ends_on', '<', now()->toDateString())->get() as $period) {
            if ($period->waves()->whereNotIn('status', ['closed', 'announced', 'completed'])->doesntExist()) {
                app(PeriodService::class)->closePeriod($period);
                $count++;
            }
        }

        return $count;
    }
}
