<?php

namespace Modules\Admission\Support;

use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\RegistrationPaymentStatus;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Enums\VerificationStatus;
use Modules\Admission\Models\Application;

/**
 * Status ringkas pendaftar `current_stage` — DITURUNKAN dari state machine,
 * dievaluasi berurutan (docs/pmb/09 §3), bukan sumber kebenaran.
 */
final class ApplicationStageResolver
{
    public const LABELS = [
        'enrolled' => 'Selamat, Anda resmi menjadi mahasiswa',
        're_registration' => 'Daftar ulang',
        're_registration_closed' => 'Daftar ulang tidak diselesaikan',
        'result_available' => 'Hasil seleksi tersedia',
        'withdrawn' => 'Ditarik',
        'void' => 'Tidak dikirim',
        'admin_rejected' => 'Tidak lolos administrasi',
        'in_selection' => 'Proses seleksi',
        'revision_needed' => 'Perlu perbaikan',
        'under_verification' => 'Sedang diverifikasi',
        'awaiting_payment' => 'Menunggu pembayaran',
        'draft' => 'Melengkapi formulir',
    ];

    public static function resolve(Application $application): string
    {
        $reRegistration = $application->relationLoaded('reRegistration') ? $application->reRegistration : $application->reRegistration()->first();
        $decision = $application->relationLoaded('currentDecision') ? $application->currentDecision : $application->currentDecision()->first();

        if ($reRegistration !== null) {
            if ($reRegistration->status === ReRegistrationStatus::Enrolled) {
                return 'enrolled';
            }

            if ($reRegistration->status->isActive() && $decision?->publication_status === PublicationStatus::Published) {
                return 're_registration';
            }

            if (in_array($reRegistration->status, [ReRegistrationStatus::Declined, ReRegistrationStatus::Expired, ReRegistrationStatus::Cancelled], true)) {
                return 're_registration_closed';
            }
        }

        if ($decision !== null && $decision->publication_status === PublicationStatus::Published) {
            return 'result_available';
        }

        if ($application->form_status === FormStatus::Withdrawn) {
            return 'withdrawn';
        }

        if ($application->form_status === FormStatus::Void) {
            return 'void';
        }

        if ($application->verification_status === VerificationStatus::Rejected) {
            return 'admin_rejected';
        }

        if (in_array($application->selection_status, [SelectionStatus::Eligible, SelectionStatus::Scheduled, SelectionStatus::Assessed, SelectionStatus::Absent, SelectionStatus::Ranked, SelectionStatus::Disqualified], true)) {
            return 'in_selection';
        }

        if ($application->verification_status === VerificationStatus::RevisionRequested) {
            return 'revision_needed';
        }

        $paid = in_array($application->registration_payment_status, [RegistrationPaymentStatus::Paid, RegistrationPaymentStatus::NotRequired], true);

        if ($application->verification_status === VerificationStatus::InReview
            || ($application->form_status === FormStatus::Submitted && $paid)) {
            return 'under_verification';
        }

        // Urutan 09 §3: tagihan pendaftaran belum lunas & diperlukan dievaluasi sebelum `draft`.
        if (! $paid) {
            return 'awaiting_payment';
        }

        return 'draft';
    }

    /**
     * @param  array<string, string>  $overrides  label kustom tenant (`pmb.status_labels`)
     */
    public static function label(string $stage, array $overrides = []): string
    {
        return $overrides[$stage] ?? self::LABELS[$stage] ?? $stage;
    }
}
