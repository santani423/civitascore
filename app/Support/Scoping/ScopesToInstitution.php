<?php

namespace App\Support\Scoping;

interface ScopesToInstitution
{
    /**
     * Column names on this model that participate in institution scoping,
     * e.g. ['university_id', 'campus_id', 'faculty_id', 'study_program_id'].
     *
     * @return array<int, string>
     */
    public function institutionScopeColumns(): array;
}
