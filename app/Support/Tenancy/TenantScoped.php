<?php

namespace App\Support\Tenancy;

use App\Support\Scoping\BelongsToInstitutionScope;

/**
 * Apply to any model that implements App\Support\Scoping\ScopesToInstitution
 * and returns ['university_id'] from institutionScopeColumns(). Wires the
 * existing BelongsToInstitutionScope global scope (so reads are
 * automatically filtered to the current tenant) and auto-fills
 * university_id from TenantContext on create when not explicitly set.
 *
 * A model in "platform" context (TenantContext has no university — e.g. a
 * Super Admin route not scoped to any tenant) is NOT auto-filled and NOT
 * scope-restricted; callers creating tenant data outside a resolved tenant
 * context must set university_id explicitly (seeders, platform-level admin
 * actions on behalf of a specific tenant).
 */
trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope(new BelongsToInstitutionScope());

        static::creating(function ($model): void {
            if ($model->getAttribute('university_id') !== null) {
                return;
            }

            $universityId = app(TenantContext::class)->universityId();

            if ($universityId !== null) {
                $model->setAttribute('university_id', $universityId);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function institutionScopeColumns(): array
    {
        return ['university_id'];
    }
}
