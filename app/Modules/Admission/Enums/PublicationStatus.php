<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-09 dimensi publikasi. */
enum PublicationStatus: string
{
    use TransitionsState;

    case Unpublished = 'unpublished';
    case Published = 'published';
    case Superseded = 'superseded';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unpublished => [self::Published, self::Superseded],
            self::Published => [self::Superseded],
            self::Superseded => [],
        };
    }
}
