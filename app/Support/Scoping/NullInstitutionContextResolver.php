<?php

namespace App\Support\Scoping;

/**
 * Phase 1 default binding: no institution tables exist yet, so every actor
 * is unrestricted. Later phases swap this binding for a resolver that reads
 * the authenticated user's university/campus/faculty/study-program.
 */
final class NullInstitutionContextResolver implements InstitutionContextResolver
{
    public function resolve(): array
    {
        return [];
    }
}
