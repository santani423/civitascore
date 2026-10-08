<?php

namespace Modules\AuditLog\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
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
     * $reason defaults to the request's `reason` input / X-Change-Reason
     * header; pass it explicitly when the caller already validated it.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(Model $model, AuditAction $action, array $oldValues, array $newValues, ?string $reason = null): AuditLog
    {
        // Atribut "masked" dicatat tersamar (****1234) — perubahannya tetap
        // terlacak tanpa audit log menjadi jalur kebocoran. Selain itu,
        // atribut $hidden dibuang sepenuhnya seperti sebelumnya.
        $masked = $this->maskedAttributes($model);
        $hidden = array_diff($model->getHidden(), $masked);
        $oldValues = $this->mask($model, $this->withoutHidden($oldValues, $hidden), $masked);
        $newValues = $this->mask($model, $this->withoutHidden($newValues, $hidden), $masked);

        return AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'reason' => $reason ?? $this->request->input('reason') ?? $this->request->header('X-Change-Reason'),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Aksi non-CRUD atas sebuah record (setujui, verifikasi, unduh, lihat
     * data sensitif, tautkan akun, dst.) — `context` disimpan sebagai
     * new_values supaya tampil di detail audit log.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordAction(Model $model, AuditAction $action, array $context = [], ?string $reason = null): AuditLog
    {
        return $this->record($model, $action, [], $context, $reason);
    }

    public static function maskValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;
        $visible = mb_strlen($value) > 4 ? mb_substr($value, -4) : '';

        return '****'.$visible;
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
    /**
     * @return array<int, string>
     */
    private function maskedAttributes(Model $model): array
    {
        return property_exists($model, 'auditMasked') ? (array) $model->auditMasked : [];
    }

    /**
     * Nilai mentah dari getAttributes()/getChanges() untuk kolom ber-cast
     * `encrypted` masih berupa ciphertext — didekripsi dulu sebelum
     * disamarkan, supaya 4 digit terakhir yang tampil memang nilai aslinya.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $masked
     * @return array<string, mixed>
     */
    private function mask(Model $model, array $values, array $masked): array
    {
        foreach ($masked as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];

            if (is_string($value) && $model->hasCast($key, ['encrypted'])) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    // Sudah plaintext (mis. nilai yang baru di-set di memori).
                }
            }

            $values[$key] = self::maskValue($value);
        }

        return $values;
    }

    private function withoutHidden(array $values, array $hidden): array
    {
        foreach ($hidden as $key) {
            unset($values[$key]);
        }

        return $values;
    }
}
