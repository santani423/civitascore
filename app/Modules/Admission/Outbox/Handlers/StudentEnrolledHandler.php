<?php

namespace Modules\Admission\Outbox\Handlers;

use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Support\PdfRenderer;
use Modules\Admission\Support\PmbNotifier;
use Modules\Admission\Support\QrCode;
use Modules\FileManagement\Models\FileUpload;

/** Tahap 16–17: bukti registrasi PDF + notifikasi `pmb.student.enrolled` (NIM, aktivasi, orientasi). */
class StudentEnrolledHandler implements OutboxHandler
{
    public function __construct(private readonly PdfRenderer $pdf, private readonly PmbNotifier $notifier) {}

    public function handle(OutboxEvent $event): void
    {
        $conversion = StudentConversion::query()->with(['application.account.profile', 'application.university', 'application.period', 'reRegistration.decision.studyProgram', 'reRegistration.decision.studySystem'])->findOrFail($event->payload['conversion_id']);
        $reRegistration = $conversion->reRegistration;
        $exists = FileUpload::query()->where('fileable_type', $reRegistration->getMorphClass())->where('fileable_id', $reRegistration->getKey())->exists();

        if (! $exists) {
            $application = $conversion->application;
            $invoice = $application->invoices()->where('type', 'initial_tuition')->first();
            $file = $this->pdf->render('admission::pdf.registration-proof', [
                'conversion' => $conversion,
                'application' => $application,
                'decision' => $reRegistration->decision,
                'invoice' => $invoice,
                'qr' => QrCode::pngDataUri($conversion->nim.'|'.$application->registration_number),
            ], 'bukti-registrasi-'.$conversion->nim.'.pdf', [(string) ($application->config_snapshot['period_code'] ?? 'pmb'), (string) $application->getKey(), 'registration']);
            $file->forceFill(['fileable_type' => $reRegistration->getMorphClass(), 'fileable_id' => $reRegistration->getKey()])->save();
        }

        $this->notifier->sendToApplicant($conversion->application->account, 'pmb.student.enrolled', ['nim' => (string) $conversion->nim]);
    }
}
