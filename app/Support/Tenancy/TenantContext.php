<?php

namespace App\Support\Tenancy;

/**
 * Request-scoped holder of "which university is this request operating as."
 * Bound as a singleton (see AppServiceProvider) — one instance per request
 * under the traditional PHP-FPM/artisan-serve lifecycle. Populated by
 * ResolveUniversityMiddleware, read by UniversityInstitutionContextResolver
 * (which feeds the existing BelongsToInstitutionScope global scope) and by
 * TenantScoped models when auto-filling university_id on create.
 *
 * A null university id is a deliberate, valid state — it means "platform/
 * super-admin context, no tenant selected," under which tenant-scoped
 * global scopes apply no restriction at all (see BelongsToInstitutionScope).
 */
class TenantContext
{
    private ?string $universityId = null;

    public function universityId(): ?string
    {
        return $this->universityId;
    }

    public function setUniversityId(?string $universityId): void
    {
        $this->universityId = $universityId;
    }

    public function hasUniversity(): bool
    {
        return $this->universityId !== null;
    }
}
