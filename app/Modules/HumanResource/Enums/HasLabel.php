<?php

namespace Modules\HumanResource\Enums;

/**
 * Seluruh enum SDM punya label bahasa Indonesia — dipakai resource
 * (`*_label`), opsi dropdown (HrOptionsController), dan laporan.
 */
interface HasLabel
{
    public function label(): string;
}
