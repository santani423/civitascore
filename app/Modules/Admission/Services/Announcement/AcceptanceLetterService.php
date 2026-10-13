<?php

namespace Modules\Admission\Services\Announcement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\LetterStatus;
use Modules\Admission\Models\AcceptanceLetter;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Support\HtmlSanitizer;
use Modules\Admission\Support\PdfRenderer;
use Modules\Admission\Support\QrCode;
use Modules\Admission\Support\SequenceGenerator;

/**
 * Letter of Acceptance (FR-ANN-05): nomor `LOA/{PERIODE}/{SEQ:5}`, kode
 * verifikasi acak + QR ke halaman verifikasi publik, PDF privat. TTE: kolom
 * `signature_reference` disiapkan, penandatanganan butuh penyedia (SRC-TTE-01).
 */
class AcceptanceLetterService
{
    public function __construct(private readonly SequenceGenerator $sequences, private readonly PdfRenderer $pdf) {}

    public function issue(AdmissionDecision $decision): ?AcceptanceLetter
    {
        if ($decision->decision !== Decision::Accepted || ! $decision->is_current) {
            return null;
        }

        $letter = DB::transaction(function () use ($decision): AcceptanceLetter {
            $existing = AcceptanceLetter::query()->where('decision_id', $decision->getKey())->lockForUpdate()->first();

            if ($existing !== null) {
                return $existing;
            }

            $period = (string) ($decision->application->config_snapshot['period_code'] ?? 'PMB');
            $seq = $this->sequences->next('loa_number', $period, (string) $decision->university_id);

            return AcceptanceLetter::query()->create([
                'decision_id' => $decision->getKey(),
                'letter_number' => 'LOA/'.$period.'/'.SequenceGenerator::pad($seq, 5),
                'verification_code' => Str::lower(Str::random(24)),
                'status' => LetterStatus::Pending,
            ]);
        });

        if ($letter->file_upload_id !== null) {
            return $letter;
        }

        $application = $decision->application()->with(['account.profile', 'university', 'wave', 'period', 'admissionPath'])->first();
        $verificationUrl = rtrim((string) config('pmb.frontend_url'), '/').'/pmb/verifikasi-loa/'.$letter->verification_code;
        $template = ContentService::activeVersion(ContentType::LoaTemplate);
        $placeholders = [
            '{{applicant_name}}' => e($application->account->profile?->full_name ?? $application->account->name),
            '{{registration_number}}' => e((string) $application->registration_number),
            '{{program_name}}' => e((string) $decision->studyProgram?->name),
            '{{study_system}}' => e((string) $decision->studySystem?->name),
            '{{deadline}}' => e(optional($application->reRegistration?->effectiveDeadline())->format('d-m-Y H:i') ?? '-'),
            '{{letter_number}}' => e($letter->letter_number),
            '{{university_name}}' => e((string) $application->university?->name),
            '{{period_name}}' => e((string) $application->period?->name),
            '{{path_name}}' => e((string) $application->admissionPath?->name),
            '{{issued_date}}' => now()->format('d-m-Y'),
            '{{verification_url}}' => e($verificationUrl),
        ];

        $file = $this->pdf->render('admission::pdf.loa', [
            'letter' => $letter,
            'decision' => $decision,
            'application' => $application,
            'customBody' => $template ? strtr((string) HtmlSanitizer::clean($template->body), $placeholders) : null,
            'qr' => QrCode::pngDataUri($verificationUrl),
            'verificationUrl' => $verificationUrl,
        ], str_replace('/', '-', $letter->letter_number).'.pdf', [(string) ($application->config_snapshot['period_code'] ?? 'pmb'), (string) $application->getKey(), 'loa']);

        $letter->forceFill(['file_upload_id' => $file->getKey(), 'status' => LetterStatus::Issued, 'issued_at' => now()])->save();

        return $letter->refresh();
    }

    /**
     * Verifikasi keaslian publik (`/pmb/public/loa/{code}`): nama tersamar,
     * prodi, tanggal terbit, status valid/dicabut.
     *
     * @return array<string, mixed>|null
     */
    public function verify(string $code): ?array
    {
        $letter = AcceptanceLetter::query()->withoutGlobalScopes()->where('verification_code', $code)->first();

        if ($letter === null) {
            return null;
        }

        $decision = AdmissionDecision::query()->withoutGlobalScopes()->with(['studyProgram' => fn ($q) => $q->withoutGlobalScopes(), 'studySystem' => fn ($q) => $q->withoutGlobalScopes()])->find($letter->decision_id);
        $application = $decision?->application()->withoutGlobalScopes()->with(['account' => fn ($q) => $q->withoutGlobalScopes()])->first();
        $name = (string) ($application?->account?->name ?? '');
        $masked = implode(' ', array_map(fn ($part) => mb_substr($part, 0, 1).str_repeat('*', max(1, mb_strlen($part) - 1)), array_filter(explode(' ', $name))));

        return [
            'valid' => $letter->status === LetterStatus::Issued,
            'revoked' => $letter->status === LetterStatus::Revoked,
            'letter_number' => $letter->letter_number,
            'name_masked' => $masked,
            'program' => $decision?->studyProgram?->name,
            'study_system' => $decision?->studySystem?->name,
            'issued_at' => $letter->issued_at?->toIso8601String(),
            'revoked_at' => $letter->revoked_at?->toIso8601String(),
        ];
    }
}
