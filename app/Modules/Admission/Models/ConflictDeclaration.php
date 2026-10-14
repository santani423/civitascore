<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;

/**
 * @property string $id
 * @property string $university_id
 * @property string $user_id
 * @property string $application_id
 * @property string $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ConflictDeclaration extends PmbModel
{
    protected $table = 'pmb_conflict_declarations';
}
