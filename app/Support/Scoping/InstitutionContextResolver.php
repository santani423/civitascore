<?php

namespace App\Support\Scoping;

interface InstitutionContextResolver
{
    /**
     * The current actor's institution context, as scope-column => allowed id
     * (or list of ids) pairs. Omitting a column leaves it unrestricted.
     *
     * @return array<string, string|array<int, string>>
     */
    public function resolve(): array;
}
