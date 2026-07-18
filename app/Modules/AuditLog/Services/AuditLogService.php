<?php

namespace Modules\AuditLog\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\ActivityLog;
use Modules\AuditLog\Models\AuditLog;

class AuditLogService
{
    public function __construct(private readonly Request $request) {}

    /**
     * Write a before/after ledger entry for a sensitive model mutation.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(Model $model, AuditAction $action, array $oldValues, array $newValues): AuditLog
    {
        $hidden = $model->getHidden();
        $oldValues = $this->withoutHidden($oldValues, $hidden);
        $newValues = $this->withoutHidden($newValues, $hidden);

        return AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'reason' => $this->request->input('reason') ?? $this->request->header('X-Change-Reason'),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Write a human-readable activity feed entry (distinct from the audit
     * ledger above — this is for UI timelines, not compliance evidence).
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(string $logName, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'log_name' => $logName,
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $hidden
     * @return array<string, mixed>
     */
    private function withoutHidden(array $values, array $hidden): array
    {
        foreach ($hidden as $key) {
            unset($values[$key]);
        }

        return $values;
    }
}
