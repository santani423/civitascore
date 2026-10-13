<?php

namespace Modules\Admission\Models;

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
