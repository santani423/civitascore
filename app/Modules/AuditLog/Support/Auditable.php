<?php

namespace Modules\AuditLog\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;

/**
 * Attach to any model that must keep a before/after audit trail (e.g. Role,
 * Permission, User). Deliberately not attached to AuditLog/ActivityLog
 * themselves — auditing the auditor is noise, not signal.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogService::class)->record($model, AuditAction::Created, [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            app(AuditLogService::class)->record($model, AuditAction::Updated, $original, $changes);
        });

        static::deleted(function (Model $model): void {
            app(AuditLogService::class)->record($model, AuditAction::Deleted, $model->getAttributes(), []);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model): void {
                app(AuditLogService::class)->record($model, AuditAction::Restored, [], $model->getAttributes());
            });
        }
    }
}
