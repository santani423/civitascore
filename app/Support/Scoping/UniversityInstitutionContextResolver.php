<?php

namespace App\Support\Scoping;

use App\Support\Tenancy\TenantContext;

/**
 * Real implementation of the institution-scoping seam, backed by
 * TenantContext (set by ResolveUniversityMiddleware). Replaces
 * NullInstitutionContextResolver now that the `universities` table exists —
 * see AppServiceProvider::register() for the binding swap.
 */
final class UniversityInstitutionContextResolver implements InstitutionContextResolver
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function resolve(): array
    {
        $universityId = $this->tenant->universityId();

        return $universityId === null ? [] : ['university_id' => $universityId];
    }
}
