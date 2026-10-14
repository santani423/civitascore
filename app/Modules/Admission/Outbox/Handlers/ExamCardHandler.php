<?php

namespace Modules\Admission\Outbox\Handlers;

use Illuminate\Support\Facades\Storage;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Exam\ExamService;
use Modules\Admission\Support\PdfRenderer;
use Modules\Admission\Support\QrCode;

/** Kartu peserta PDF ber-QR token bertanda tangan (FR-EXM-05). Dibangkitkan ulang saat sesi berubah. */
class ExamCardHandler implements OutboxHandler
{
    public function __construct(private readonly PdfRenderer $pdf, private readonly ExamService $exams) {}

    public function handle(OutboxEvent $event): void
    {
        $participant = ExamParticipant::query()->with(['application.account.profile', 'application.documents.documentType', 'application.documents.currentVersion.fileUpload', 'session.room.venue', 'schedule', 'application.university'])->find($event->payload['participant_id']);

        if ($participant === null) {
            return;
        }

        $photo = $participant->application->documents->first(fn ($d) => $d->documentType?->code === 'PHOTO' && $d->currentVersion?->fileUpload);
        $photoData = null;

        if ($photo !== null) {
            $file = $photo->currentVersion->fileUpload;
            $photoData = 'data:'.$file->mime_type.';base64,'.base64_encode((string) Storage::disk($file->disk)->get($file->path));
        }

        $file = $this->pdf->render('admission::pdf.exam-card', [
            'participant' => $participant,
            'application' => $participant->application,
            'photo' => $photoData,
            'qr' => QrCode::pngDataUri($this->exams->cardToken($participant)),
        ], 'kartu-'.$participant->participant_number.'.pdf', [(string) ($participant->application->config_snapshot['period_code'] ?? 'pmb'), (string) $participant->application_id, 'exam-cards']);

        $participant->forceFill(['card_file_id' => $file->getKey()])->save();
    }
}
