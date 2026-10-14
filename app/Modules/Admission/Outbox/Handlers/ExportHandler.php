<?php

namespace Modules\Admission\Outbox\Handlers;

use App\Models\User;
use Modules\Admission\Enums\ExportStatus;
use Modules\Admission\Models\ExportLog;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Outbox\OutboxHandler;
use Modules\Admission\Services\Reporting\ReportService;
use Throwable;

class ExportHandler implements OutboxHandler
{
    public function __construct(private readonly ReportService $reports) {}

    public function handle(OutboxEvent $event): void
    {
        $log = ExportLog::query()->find($event->payload['export_id']);

        if ($log === null || $log->status === ExportStatus::Ready) {
            return;
        }

        // Ekspor berjalan dalam konteks izin pembuatnya (scope & kolom, BR-42).
        $user = User::query()->find($log->user_id);
        auth()->setUser($user);

        try {
            $this->reports->generateExport($log);
        } catch (Throwable $e) {
            $log->forceFill(['status' => ExportStatus::Failed, 'error_message' => mb_substr($e->getMessage(), 0, 1000)])->save();

            throw $e;
        }
    }
}
