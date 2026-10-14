<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\ExportLog;
use Modules\Admission\Models\SensitiveAccessLog;
use Modules\AuditLog\Models\AuditLog;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Tenancy\Models\UniversitySetting;

/** Jejak audit PMB, log akses data sensitif, log ekspor (FR-AUD, 11 §A.17, ADM-96..98). */
class AuditController extends AdminController
{
    public function auditLogs(Request $request): JsonResponse
    {
        $request->validate(['entity' => ['nullable', 'string', 'max:60'], 'entity_id' => ['nullable', 'string', 'max:40'], 'user_id' => ['nullable', 'string'], 'action' => ['nullable', 'string', 'max:30'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $types = $this->auditableTypes();

        $query = AuditLog::query()->with('user:id,name')
            ->whereIn('auditable_type', $request->filled('entity') ? array_filter($types, fn ($t) => class_basename($t) === $request->string('entity')->toString()) : $types)
            ->when($request->filled('entity_id'), fn ($q) => $q->where('auditable_id', $request->string('entity_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->string('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<', $request->date('to')?->addDay()))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (AuditLog $l) => [
            'id' => $l->getKey(), 'entity' => class_basename((string) $l->auditable_type), 'entity_id' => $l->auditable_id,
            'action' => $l->action?->value ?? $l->action, 'user' => $l->user?->name ?? 'Sistem/Pendaftar',
            'old_values' => $l->old_values, 'new_values' => $l->new_values, 'reason' => $l->reason,
            'ip_address' => $l->ip_address, 'created_at' => $l->created_at?->toIso8601String(),
        ], 50);
    }

    public function sensitiveLogs(Request $request): JsonResponse
    {
        $query = SensitiveAccessLog::query()->with('user:id,name')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->string('user_id')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->string('subject_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (SensitiveAccessLog $l) => [
            'id' => $l->getKey(), 'user' => $l->user?->name, 'subject' => class_basename((string) $l->subject_type), 'subject_id' => $l->subject_id,
            'field_or_document' => $l->field_or_document, 'purpose' => $l->purpose, 'ip_address' => $l->ip_address, 'created_at' => $l->created_at?->toIso8601String(),
        ], 50);
    }

    public function exportLogs(Request $request): JsonResponse
    {
        $query = ExportLog::query()->with('user:id,name')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->string('user_id')))
            ->when($request->boolean('personal_only'), fn ($q) => $q->where('contains_personal_data', true))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (ExportLog $l) => [
            'id' => $l->getKey(), 'user' => $l->user?->name, 'report_code' => $l->report_code, 'format' => $l->format,
            'filters' => $l->filters, 'columns' => $l->columns, 'row_count' => $l->row_count, 'reason' => $l->reason,
            'contains_personal_data' => $l->contains_personal_data, 'status' => $l->status->value, 'created_at' => $l->created_at?->toIso8601String(),
        ], 50);
    }

    /** Ringkasan 30 hari untuk auditor (ADM-96). */
    public function summary(): JsonResponse
    {
        $since = now()->subDays(30);

        return ApiResponse::success([
            'audit_events' => AuditLog::query()->whereIn('auditable_type', $this->auditableTypes())->where('created_at', '>=', $since)->count(),
            'sensitive_access' => SensitiveAccessLog::query()->where('created_at', '>=', $since)->count(),
            'sensitive_by_user' => SensitiveAccessLog::query()->with('user:id,name')->where('created_at', '>=', $since)->selectRaw('user_id, count(*) as total')->groupBy('user_id')->orderByDesc('total')->limit(10)->get()
                ->map(fn ($r) => ['user' => $r->user?->name, 'total' => (int) $r->total]),
            'exports' => ExportLog::query()->where('created_at', '>=', $since)->count(),
            'personal_exports' => ExportLog::query()->where('created_at', '>=', $since)->where('contains_personal_data', true)->count(),
            'entities' => collect($this->auditableTypes())->map(fn ($t) => class_basename($t))->sort()->values(),
        ]);
    }

    /** @return array<int, string> */
    private function auditableTypes(): array
    {
        static $types = null;

        if ($types === null) {
            $types = collect(glob(dirname(__DIR__, 2).'/Models/*.php') ?: [])
                ->map(fn ($file) => 'Modules\\Admission\\Models\\'.basename($file, '.php'))
                ->reject(fn ($class) => $class === 'Modules\\Admission\\Models\\PmbModel')
                ->push(UniversitySetting::class, NotificationTemplate::class)
                ->values()->all();
        }

        return $types;
    }
}
