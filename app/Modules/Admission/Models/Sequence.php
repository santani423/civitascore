<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;

/**
 * @property string $id
 * @property string $university_id
 * @property string $scope
 * @property string $key
 * @property int $last_value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Sequence extends PmbModel
{
    protected $table = 'pmb_sequences';

    protected function casts(): array
    {
        return [
            'last_value' => 'integer',
        ];
    }
}
