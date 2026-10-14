<?php

namespace Modules\Admission\Support;

use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationStatusHistory;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Tenancy\Models\UniversitySetting;

/**
 * Menulis riwayat transisi status (`pmb_application_status_histories`) DAN
 * audit log dalam transaksi pemanggil (09 konvensi implementasi, NFR-21).
 */
class HistoryRecorder
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Application $application,
        Lifecycle $lifecycle,
        BackedEnum|string|null $from,
        BackedEnum|string $to,
        Actor $actor,
        ?string $reasonCode = null,
        ?string $note = null,
        array $metadata = [],
        ?Model $auditSubject = null,
    ): ApplicationStatusHistory {
        $fromValue = $from instanceof BackedEnum ? (string) $from->value : $from;
        $toValue = $to instanceof BackedEnum ? (string) $to->value : $to;

        $history = ApplicationStatusHistory::query()->create([
            'university_id' => $application->university_id,
            'application_id' => $application->getKey(),
            'lifecycle' => $lifecycle,
            'from_status' => $fromValue,
            'to_status' => $toValue,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'reason_code' => $reasonCode,
            'reason_note' => $note,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $this->audit->record(
            $auditSubject ?? $application,
            AuditAction::Updated,
            ["{$lifecycle->value}_status" => $fromValue],
            array_filter([
                "{$lifecycle->value}_status" => $toValue,
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'reason_code' => $reasonCode,
            ], fn ($value) => $value !== null),
            $note ?? $reasonCode,
        );

        return $history;
    }

    /**
     * Perubahan konfigurasi/data (old → new) dengan alasan (03 §A.4, 04 §D.4).
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function change(Model $model, array $old, array $new, ?string $reason = null, AuditAction $action = AuditAction::Updated): void
    {
        if ($old === [] && $new === []) {
            return;
        }

        $this->audit->record($model, $action, $old, $new, $reason);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function action(Model $model, AuditAction $action, array $context = [], ?string $reason = null): void
    {
        $this->audit->recordAction($model, $action, $context, $reason);
    }

    /**
     * Audit perubahan pengaturan tenant (FR-CFG-12): satu entri per kunci
     * pada baris university_settings yang bersangkutan.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function recordSettings(array $old, array $new): void
    {
        $universityId = app(TenantContext::class)->universityId();

        foreach ($new as $key => $value) {
            $row = UniversitySetting::query()->where('university_id', $universityId)->where('key', $key)->first();

            if ($row !== null && ($old[$key] ?? null) !== $value) {
                $this->audit->record($row, AuditAction::Updated, [$key => $old[$key] ?? null], [$key => $value]);
            }
        }
    }
}
